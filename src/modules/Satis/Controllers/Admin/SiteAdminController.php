<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers\Admin;

use Sprout\Controllers\Admin\HasCategoriesAdminController;
use Sprout\Helpers\ColModifierBinary;
use Sprout\Helpers\ColModifierHexIP;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Itemlist;
use Sprout\Helpers\Pdb;

/**
 * Sites admin
 */
class SiteAdminController extends HasCategoriesAdminController
{
    protected $controller_name = 'site';
    protected $friendly_name = 'Sites';
    protected $add_defaults = [
        'active' => 1,
    ];
    protected $main_columns = [];
    protected $main_delete = true;


    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
            'Token' => 'token',
            'Active' => [new ColModifierBinary(), 'active'],
        ];

        $this->initRefineBar();

        parent::__construct();
    }


    /** @inheritdoc */
    public function _getTools()
    {
        $tools = parent::_getTools();

        $url = "admin/extra/{$this->controller_name}/authLog";
        $tools['config'] = '<li class="config"><a href="' . Enc::html($url) . '">Auth Log</a></li>';

        return $tools;
    }


    /** @inheritdoc */
    public function _getEditSubActions($item_id)
    {
        $actions = parent::_getEditSubActions($item_id);

        $username = Pdb::find('sites')
            ->where(['id' => $item_id])
            ->value('username', false);

        $actions[] = [
            'url' => "admin/extra/{$this->controller_name}/authLog?username={$username}",
            'name' => 'Auth Log',
            'class' => 'icon-link-button icon-before icon-timeline',
        ];

        return $actions;
    }


    /**
     * View the auth log.
     *
     * Filters:
     *  - username
     *
     * @return array [title, content]
     */
    public function _extraAuthLog()
    {
        $page_size = 25;
        $page = min($_GET['page'] ?? 1, 1);

        $items = Pdb::find('sites_auth_log')
            ->offset(($page - 1) * $page_size)
            ->limit($page_size)
            ->orderBy('date_added DESC');

        if (!empty($_GET['username'])) {
            $items->where(['username' => $_GET['username']]);
        }

        $itemlist = new Itemlist();
        $itemlist->items = $items->all();
        $itemlist->main_columns = [
            'Date' => 'date_added',
            'IP address' => [new ColModifierHexIP(), 'ip_address'],
            'Username' => 'username',
            'Password (hash)' => 'password_hash',
            'Error' => 'error',
            'Success' => [new ColModifierBinary(), 'success'],
        ];

        return [
            'title' => 'Auth Log',
            'content' => $itemlist->render() ?: '<p>No logs.</p>',
        ];
    }

}


