<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers\Admin;

use Sprout\Controllers\Admin\NoRecordsAdminController;
use Sprout\Helpers\TwigView;
use SproutModules\Karmabunny\Satis\Models\Package;
use SproutModules\Karmabunny\Satis\Models\Site;

/**
 * Generic controller rendering the dashboard.
 */
class DashboardAdminController extends NoRecordsAdminController
{
    public $controller_name = 'dashboard';
    public $friendly_name = 'KB Packages';


    /** @inheritdoc */
    public function _intro()
    {
        $packages = Package::findAll();
        $sites = Site::findAll();

        $view = new TwigView('modules/Satis/dashboard', [
            'packages' => $packages,
            'sites' => $sites,
        ]);

        return $view->render();
    }
}
