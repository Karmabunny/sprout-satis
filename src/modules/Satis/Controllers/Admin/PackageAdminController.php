<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers\Admin;

use Sprout\Controllers\Admin\HasCategoriesAdminController;
use Sprout\Helpers\ColModifierBinary;
use Sprout\Helpers\ColModifierDate;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Json;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Request;
use Sprout\Helpers\Url;
use Sprout\Helpers\WorkerCtrl;
use SproutModules\Karmabunny\Satis\Helpers\SatisWorker;
use SproutModules\Karmabunny\Satis\Helpers\Satis;
use SproutModules\Karmabunny\Satis\Models\Package;

/**
 * Handles admin processing for packages
 */
class PackageAdminController extends HasCategoriesAdminController
{
    protected $controller_name = 'package';
    protected $friendly_name = 'Packages';
    protected $add_defaults = [
        'active' => 1,
    ];
    protected $main_columns = [];
    protected $main_delete = true;


    /** @inheritdoc */
    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
            'Active' => [new ColModifierBinary(), 'active'],
            'Repository' => 'repo_url',
            'Last Build' => [new ColModifierDate('d/m/Y g:ia'), 'last_build_time'],
            'Build OK' => [new ColModifierBinary(), 'build_success'],
        ];

        $this->main_actions['Build'] = "admin/call/{$this->controller_name}/buildPackage/%%";

        $this->initRefineBar();

        parent::__construct();
    }


    /** @inheritdoc */
    public function _getTools()
    {
        $tools = parent::_getTools();

        $url = "admin/call/{$this->controller_name}/buildAllPackages";
        $tools['worker'] = '<li class="worker"><a href="' . Enc::html($url) . '">Build Packages</a></li>';

        $url = "admin/call/{$this->controller_name}/viewConfig";
        $tools['config'] = '<li class="config"><a href="' . Enc::html($url) . '">View Config</a></li>';

        return $tools;
    }


    /** @inheritdoc */
    public function _getEditSubActions($item_id)
    {
        $actions = parent::_getEditSubActions($item_id);

        $actions[] = [
            'url' => "admin/call/{$this->controller_name}/buildPackage/{$item_id}",
            'name' => 'Build',
            'class' => 'icon-link-button icon-before icon-send',
        ];

        return $actions;
    }


    /**
     * Preview the satis config, for debugging.
     */
    public function viewConfig()
    {
        $config = Satis::getConfig();
        $config = Json::encode($config, true);

        echo '<pre>';
        echo $config;
        echo '</pre>';
    }


    /**
     * Build all packages.
     */
    public function buildAllPackages()
    {
        $worker = WorkerCtrl::start(SatisWorker::class);
        Url::redirect($worker['log_url']);
    }


    /**
     * Build a single package.
     */
    public function buildPackage($item_id)
    {
        $package = Package::findOne(['id' => $item_id]);
        $job = WorkerCtrl::start(SatisWorker::class, [$package->repo_url]);
        $package->setWorker($job['job_id']);

        Notification::confirm("Build started: <a href='{$job['log_url']}'>{$package->name}</a>", 'html');
        Url::redirect(Request::getHeader('referer') ?: "admin/{$this->controller_name}/edit/{$item_id}");
    }
}


