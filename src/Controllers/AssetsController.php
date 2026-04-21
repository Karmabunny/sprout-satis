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
use Sprout\Helpers\Json;
use Sprout\Helpers\Request;
use Sprout\Helpers\Router;
use Sprout\Helpers\Validator;
use SproutModules\Karmabunny\Satis\Helpers\Assets;
use SproutModules\Karmabunny\Satis\Helpers\ControllerAuthTrait;
use SproutModules\Karmabunny\Satis\Models\Package;
use Throwable;

/**
 * Handles serving the satis repo.
 */
class AssetsController extends Controller
{
    use ControllerAuthTrait;

    /**
     * Serve asset files behind sites auth.
     */
    #[Route('a1/*')]
    public function archive()
    {
        $realm = Kohana::config('satis.realm');

        if (!$this->hasAuth()) {
            header("www-authenticate: basic realm=\"$realm\", charset=\"UTF-8\"");
            http_response_code(401);
            echo '401', "\n";
            exit;
        }

        if (!$this->checkAuth()) {
            http_response_code(403);
            echo '403', "\n";
            exit;
        }

        $path = Assets::OUTPUT_DIR . '/' . substr(Router::$current_uri, 3);

        if (!is_file($path)) {
            http_response_code(404);
            echo '404' , "\n";
            exit;
        }

        header('cache-control: no-store, no-cache, max-age=0');
        header('content-type: ' . mime_content_type($path));
        readfile($path);
    }

    /**
     * Publish an asset for a package.
     */
    #[Route('assets/publish')]
    public function publish()
    {
        Kohana::closeBuffers(false);
        header('Content-Type: application/json');

        // A little safety net.
        set_exception_handler(function(Throwable $error) {
            $status = 500;

            if ($error instanceof HttpExceptionInterface) {
                $status = $error->getStatusCode();
            }

            if ($status == 500) {
                $id = Kohana::logException($error, false);
            }

            http_response_code($status);
            Json::error($error);
        });

        $validator = new Validator($_GET);
        $validator->required(['package', 'ref']);

        if ($validator->hasErrors()) {
            Json::error([
                'errors' => $validator->getFieldErrors(),
            ]);
        }

        $signature = Request::getHeader('x-publish-signature');
        if (!$signature) {
            throw new HttpException(401, 'Invalid signature');
        }

        $package = $_GET['package'];
        $ref = $_GET['ref'];

        /** @var Package|null $package */
        $package = Package::find()
            ->where(['name' => $package])
            ->throw(false)
            ->one();

        if (!$package) {
            throw new HttpException(404, 'Package not found');
        }

        $body = Request::getRawBody();

        // https://docs.github.com/en/webhooks-and-events/webhooks/securing-your-webhooks
        $digest = 'sha256=' . hash_hmac('sha256', $body, $package->webhook_token);

        if (!hash_equals($digest, $signature)) {
            throw new HttpException(401, 'Invalid signature');
        }

        if (!is_string($body)) {
            throw new HttpException(400, 'Invalid body');
        }

        if (substr($body, 0, 4) !== "PK\x03\x04") {
            throw new HttpException(400, 'Invalid zip');
        }

        $path = Assets::OUTPUT_DIR . "/{$package->name}/{$ref}.zip";
        $exists = file_exists($path);

        $overwrite = filter_var(Request::getHeader('x-publish-overwrite'), FILTER_VALIDATE_BOOLEAN);

        if ($exists and !$overwrite) {
            throw new HttpException(400, 'Reference already exists');
        }

        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $body);

        http_response_code($exists ? 200 : 201);
        Json::confirm();
    }
}
