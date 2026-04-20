<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers;

use karmabunny\router\Route;
use Kohana;
use Sprout\Controllers\Controller;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Request;
use Sprout\Helpers\Router;
use Sprout\Helpers\Url;
use SproutModules\Karmabunny\Satis\Helpers\AuthLog;
use SproutModules\Karmabunny\Satis\Helpers\ControllerAuthTrait;
use SproutModules\Karmabunny\Satis\Helpers\Satis;
use SproutModules\Karmabunny\Satis\Models\Site;

/**
 * Handles serving the satis repo.
 */
class PackageController extends Controller
{
    use ControllerAuthTrait;


    /**
     * Serve the repo index behind admin auth.
     */
    #[Route('')]
    public function public()
    {
        if (!AdminAuth::isLoggedIn()) {
            Url::redirect('admin/login');
        }

        readfile(Satis::OUTPUT_DIR . '/index.html');
    }


    /**
     * Serve repo files behind sites auth.
     */
    #[Route('archive/*')]
    #[Route('include/*')]
    #[Route('p2/*')]
    #[Route('packages.json')]
    public function repo()
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

        $path = Satis::OUTPUT_DIR . '/' . Router::$current_uri;

        if (!is_file($path)) {
            http_response_code(404);
            echo '404' , "\n";
            exit;
        }

        header('cache-control: no-store, no-cache, max-age=0');
        header('content-type: ' . mime_content_type($path));
        readfile($path);
    }
}
