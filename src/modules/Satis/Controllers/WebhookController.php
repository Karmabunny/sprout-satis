<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers;

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

        $output = new StreamOutput(fopen('php://stdout', 'w'));
        $ok = Satis::build($output, [$package->repo_url]);
        Satis::updatePackages([$package->repo_url], $ok);

        echo "\n";
        echo $ok ? "ok\n" : "error\n";
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

        set_exception_handler(function(Throwable $error) {
            if (!$error instanceof HttpExceptionInterface) {
                Kohana::logException($error, false);
            }

            Json::error($error);
        });

        $signature = Request::getHeader('x-hub-signature-256');
        if (!$signature) {
            throw new HttpException(401, 'Invalid signature');
        }

        $body = Request::getRawBody();
        $json = Json::decode($body);

        $full_name = $json['repository']['full_name'] ?? null;

        // Lie.
        if (!$full_name) {
            throw new HttpException(401, 'Invalid signature');
        }

        /** @var Package|null $package */
        $package = Package::find()
            ->where(['OR' => [
                ['repo_url' => 'git@github.com:' . $full_name],
                ['repo_url' => 'git@github.com:' . $full_name . '.git'],
                ['repo_url' => 'https://' . $full_name],
                ['repo_url' => 'https://' . $full_name . '.git'],
            ]])
            ->throw(false)
            ->one();

        // Big fat liar.
        if (!$package) {
            throw new HttpException(401, 'Invalid signature');
        }

        // https://docs.github.com/en/webhooks-and-events/webhooks/securing-your-webhooks
        $digest = 'sha256=' . hash_hmac('sha256', $body, $package->webhook_token);

        if (!hash_equals($digest, $signature)) {
            throw new HttpException(401, 'Invalid signature');
        }

        $action = $json['action'] ?? '??';

        if ($action !== 'push') {
            throw new HttpException(400, "Invalid action: '{$action}', requires 'push'");
        }

        if (!$package->isBuilding()) {
            $job = WorkerCtrl::start(SatisWorker::class, [$package->repo_url]);
            $package->setWorker($job['job_id']);
        }

        Json::confirm();
    }
}
