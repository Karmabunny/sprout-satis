<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers\Admin;

use Sprout\Controllers\Admin\CategoryAdminController;


/**
* Site categories
**/
class SiteCategoryAdminController extends CategoryAdminController
{
    protected $controller_name = 'site_category';
    protected $friendly_name = 'Site Categories';
    protected $navigation_name = 'Sites';
}
