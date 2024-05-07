<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

use Sprout\Helpers\Register;


Register::adminControllers('Karmabunny\Satis', [
    'dashboard' => 'Admin\DashboardAdminController',
    'package' => 'Admin\PackageAdminController',
    'package_category' => 'Admin\PackageCategoryAdminController',
    'site' => 'Admin\SiteAdminController',
    'site_category' => 'Admin\SiteCategoryAdminController',
]);

Register::adminTile(
    'Packages',
    'list',
    '???',
    [
        'package' => 'Packages',
        'site' => 'Sites',
    ]
);
