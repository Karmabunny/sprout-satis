<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Controllers\Admin;

use Sprout\Controllers\Admin\ListAdminController;
use Sprout\Helpers\ColModifierBinary;

/**
 * Sites admin
 */
class SiteAdminController extends ListAdminController
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

}


