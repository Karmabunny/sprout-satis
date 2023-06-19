<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers;

use karmabunny\router\Route;
use Sprout\Controllers\Controller;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Request;
use Sprout\Helpers\Url;
use SproutModules\Karmabunny\Satis\Helpers\Satis;
use SproutModules\Karmabunny\Satis\Models\Site;

/**
 * Handles serving the satis repo.
 */
class PackageController extends Controller
{

    /**
     * Serve the repo index behind admin auth.
     */
    #[Route('public')]
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
    #[Route('public/*')]
    public function repo(...$segments)
    {
        static $REALM = 'Bunnysites Private Packagist';

        if (!$this->hasAuth()) {
            header("www-authenticate: basic realm=\"$REALM\", charset=\"UTF-8\"");
            http_response_code(401);
            echo '401', "\n";
            exit;
        }

        if (!$this->checkAuth()) {
            http_response_code(403);
            echo '403', "\n";
            exit;
        }

        $path = Satis::OUTPUT_DIR . '/' . implode('/', $segments);

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
     * Check if an auth has been attempted.
     *
     * This doesn't imply the auth is _valid_, just that it exists.
     *
     * @return bool
     */
    private function hasAuth()
    {
        if (AdminAuth::isLoggedIn()) {
            return true;
        }

        if (Request::getAuthorization('basic')) {
            return true;
        }

        return false;
    }


    /**
     * Check if the auth is valid.
     *
     * @return bool
     */
    private function checkAuth()
    {
        if (AdminAuth::isLoggedIn()) {
            return true;
        }

        $basic = Request::getAuthorization('basic');

        if (!$basic) {
            return false;
        }

        [$user, $pass] = explode(':', base64_decode($basic), 2) + [null, null];

        $query = Site::find()
            ->where([
                'name' => $user,
                'token' => $pass,
            ]);

        if ($query->exists()) {
            return true;
        }

        return false;
    }
}
