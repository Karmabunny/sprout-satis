<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers;

use JsonException;
use karmabunny\router\Route;
use Kohana;
use Sprout\Controllers\Controller;
use Sprout\Exceptions\HttpException;
use Sprout\Exceptions\HttpExceptionInterface;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Json;
use Sprout\Helpers\Request;
use Sprout\Helpers\WorkerCtrl;
use SproutModules\Karmabunny\Satis\Helpers\Satis;
use SproutModules\Karmabunny\Satis\Helpers\SatisWorker;
use SproutModules\Karmabunny\Satis\Helpers\WebhookLog;
use SproutModules\Karmabunny\Satis\Models\Package;
use Symfony\Component\Console\Output\StreamOutput;
use Throwable;

/**
 * Package webhooks for building things remotely.
 */
class WebhookController extends Controller
{

    /**
     * This hook is only for testing.
     */
    #[Route('hooks/build/{package}')]
    public function build(string $package)
    {
        $log = WebhookLog::create('test', 'build');

        if (!AdminAuth::isLoggedIn()) {
            throw new HttpException(401, 'Not logged in');
        }

        Kohana::closeBuffers(false);
        header('Content-Type: text/plain');

        set_exception_handler(function(Throwable $error) {
            if (!$error instanceof HttpExceptionInterface) {
                Kohana::logException($error, false);
            }

            if (AdminAuth::isLoggedIn() or !IN_PRODUCTION) {
                echo get_class($error), "\n";
                echo $error->getMessage(), "\n";
                echo $error->getTraceAsString(), "\n";
            } else {
                echo "error\n";
            }

            exit;
        });

        $package = Package::findOne(['name' => $package]);

        $log->setPackage($package->name);

        // Inline build so we can easily debug the output.
        $output = new StreamOutput(fopen('php://stdout', 'w'));
        $ok = Satis::build($output, [$package->repo_url]);
        Satis::updatePackages([$package->repo_url], $ok);

        echo "\n";
        echo $ok ? "ok\n" : "error\n";

        if ($ok) {
            $log->success();
        } else {
            $log->error('Build failed');
        }
    }


    /**
     * The github hook is documented here:
     *
     * https://docs.github.com/en/webhooks-and-events/webhooks/about-webhooks
     *
     * A testing script is provided in tools.
     */
    #[Route('hooks/github')]
    public function github()
    {
        Kohana::closeBuffers(false);
        header('Content-Type: application/json');

        $log = null;

        // A little safety net.
        // Parse-by-ref so we can retroactively create the logger.
        set_exception_handler(function(Throwable $error) use (&$log) {
            /** @var WebhookLog|null $log */

            $status = 500;

            if ($error instanceof HttpExceptionInterface) {
                $status = $error->getStatusCode();
            }

            if ($status == 500) {
                $id = Kohana::logException($error, false);

                if ($log) {
                    $log->error('SE' . $id);
                }
            }

            http_response_code($status);
            Json::error($error);
        });

        // Parse event type.
        $action = Request::getHeader('x-github-event');
        $log = WebhookLog::create('github', $action);

        $signature = Request::getHeader('x-hub-signature-256');
        if (!$signature) {
            throw new HttpException(401, 'Invalid signature');
        }

        // Parse the payload.
        try {
            $body = Request::getRawBody();
            $json = Json::decode($body);
        }
        catch (JsonException $error) {
            $log->error($error->getMessage());
            throw new HttpException(400, 'Malformed payload', $error);
        }

        $repo_url = $json['repository']['ssh_url'] ?? null;

        // Lie.
        if (!$repo_url) {
            $log->error('Missing ssh_url field');
            throw new HttpException(401, 'Invalid signature');
        }

        /** @var Package|null $package */
        $package = Package::find()
            ->where(['OR' => [
                ['repo_url' => $repo_url],
                ['repo_url' => preg_replace('/\.git$/', '', $repo_url)],
            ]])
            ->throw(false)
            ->one();

        // Big fat liar.
        if (!$package) {
            $log->error('Unknown package');
            throw new HttpException(401, 'Invalid signature');
        }

        $log->setPackage($package->name);

        // https://docs.github.com/en/webhooks-and-events/webhooks/securing-your-webhooks
        $digest = 'sha256=' . hash_hmac('sha256', $body, $package->webhook_token);

        if (!hash_equals($digest, $signature)) {
            $log->error('Invalid signature');
            throw new HttpException(401, 'Invalid signature');
        }

        if ($action === 'ping') {
            // TODO something else? record it somewhere?
            $log->success();
            Json::confirm(['message' => 'pong']);
        }

        if ($action !== 'push') {
            throw new HttpException(400, "Invalid action: '{$action}', requires 'push'");
        }

        if (!$package->isBuilding()) {
            $job = WorkerCtrl::start(SatisWorker::class, [$package->repo_url]);
            $package->setWorker($job['job_id']);
        }

        $log->success();
        Json::confirm();
    }
}
