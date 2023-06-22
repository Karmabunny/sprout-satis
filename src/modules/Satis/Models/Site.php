<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Models;

use Sprout\Helpers\Model;

/**
 * Site record.
 */
class Site extends Model
{

    /** @var bool */
    public $active;

    /** @var string */
    public $date_added;

    /** @var string */
    public $date_modified;

    /** @var string */
    public $name;

    /** @var string */
    public $token;


    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'sites';
    }
}
