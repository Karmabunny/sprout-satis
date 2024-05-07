<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers\Admin;

use Sprout\Controllers\Admin\CategoryAdminController;


/**
* Handles most processing for package categories
**/
class PackageCategoryAdminController extends CategoryAdminController
{
    protected $controller_name = 'package_category';
    protected $friendly_name = 'Package Categories';
    protected $navigation_name = 'Packages';
}
