<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers\Admin;

use karmabunny\kb\Arrays;
use Sprout\Controllers\Admin\HasCategoriesAdminController;
use Sprout\Helpers\ColModifierBinary;
use Sprout\Helpers\ColModifierDate;
use Sprout\Helpers\ColModifierHexIP;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Itemlist;
use Sprout\Helpers\Json;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
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
            'Last Webhook' => [new ColModifierDate('d/m/Y g:ia'), 'webhook_valid_time'],
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
        $tools['worker'] = '<li class="worker"><a href="' . Enc::html($url) . '">Build All Packages</a></li>';

        $url = "admin/call/{$this->controller_name}/viewConfig";
        $tools['config'] = '<li class="config"><a href="' . Enc::html($url) . '">View Config</a></li>';

        $url = "admin/extra/{$this->controller_name}/webhookLog";
        $tools['webhook'] = '<li class="config"><a href="' . Enc::html($url) . '">Webhook Log</a></li>';

        return $tools;
    }


    /** @inheritdoc */
    public function _getEditSubActions($item_id)
    {
        $actions = parent::_getEditSubActions($item_id);

        $name = Pdb::find('packages')
            ->where(['id' => $item_id])
            ->value('name', false);

        $actions[] = [
            'url' => "admin/call/{$this->controller_name}/buildPackage/{$item_id}",
            'name' => 'Build',
            'class' => 'icon-link-button icon-before icon-send',
        ];

        $actions[] = [
            'url' => "admin/extra/{$this->controller_name}/webhookLog?name=" . Enc::url($name),
            'name' => 'Webhook Log',
            'class' => 'icon-link-button icon-before icon-timeline',
        ];

        return $actions;
    }


    /** @inheritdoc */
    protected function _preSave($id, &$data)
    {
        // The token has been updated so the webhook needs to be revalidated.
        if (!empty($data['webhook_token'])) {
            $data['webhook_valid_time'] = null;
        }
    }


    /**
     * View the webhook log.
     *
     * Filters:
     * - reference
     *
     * @return array [ title, content ]
     */
    public function _extraWebhookLog()
    {
        $page_size = 25;
        $page = max($_GET['page'] ?? 1, 1);

        $filters = false;

        $items = Pdb::find('packages_webhook_log')
            ->offset(($page - 1) * $page_size)
            ->limit($page_size)
            ->orderBy('date_added DESC');

        if (!empty($_GET['id'])) {
            $filters = true;
            $items->where(['id' => $_GET['id']]);
        }

        if (!empty($_GET['name'])) {
            $filters = true;
            $items->where(['package_ref' => $_GET['name']]);
        }

        $itemlist = new Itemlist();
        $itemlist->items = $items->all();
        $itemlist->addAction('edit', "admin/extra/{$this->controller_name}/webhookLog?id=%%");
        $itemlist->main_columns = [
            'Date' => 'date_added',
            'IP address' => [new ColModifierHexIP(), 'ip_address'],
            'Provider' => 'provider',
            'Event' => 'event',
            'Package' => 'package_ref',
            'Success' => [new ColModifierBinary(), 'success'],
        ];

        $content = $itemlist->render();

        // Show extra content if we're looking at one thing in particular.
        if ($filters and $page === 1 and count($itemlist->items) === 1) {
            $item = reset($itemlist->items);

            $headers = json_decode($item['headers'], true);
            $headers = Arrays::implodeWithKeys($headers, "\r\n", ': ');
            $headers = Enc::html($headers);

            $body = json_decode($item['body']);
            $body = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $body = Enc::html($body);

            if ($item['error']) {
                $error = Enc::html($item['error']);
                $content .= <<<EOF
                    <h3>Error</h3>
                    <pre>{$error}</pre>
                EOF;
            }

            $content .= <<<EOF
                <h3>Headers</h3>
                <pre>{$headers}</pre>
                <h3>Body</h3>
                <pre>{$body}</pre>
            EOF;
        }

        return [
            'title' => 'Webhook Log',
            'content' => $content ?: '<p>No logs.</p>',
        ];
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


