<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace SproutModules\Karmabunny\HomePage\Controllers;

use Kohana;

use SproutModules\Karmabunny\HomePage\Helpers\HomePages;
use Sprout\Controllers\Controller;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Url;
use Sprout\Helpers\View;
use SproutModules\Karmabunny\Satis\Helpers\Satis;

/**
 * Handles requests for the home page
 */
class HomePageController extends Controller
{

    /**
     * Renders the home page
     *
     * @return void Outputs HTML directly
     */
    public function index()
    {
        if (!AdminAuth::isLoggedIn()) {
            Url::redirect('admin/login');
        }

        readfile(Satis::OUTPUT_DIR . '/index.html');
    }
}
