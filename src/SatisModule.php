<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis;

use Sprout\Helpers\Module;
use Sprout\Helpers\Sprout;

class SatisModule extends Module
{
    /** @inheritdoc */
    public function getVersion(): string
    {
        return Sprout::getInstalledVersion('karmabunny/sprout-satis');
    }
}
