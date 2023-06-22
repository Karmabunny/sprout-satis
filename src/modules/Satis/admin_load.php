<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

use Sprout\Helpers\Register;


Register::adminControllers('Karmabunny\Satis', [
    'package' => 'Admin\PackageAdminController',
    'package_category' => 'Admin\PackageCategoryAdminController',
    'site' => 'Admin\SiteAdminController',
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
