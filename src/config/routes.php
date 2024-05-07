<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

use SproutModules\Karmabunny\Satis\Controllers\PackageController;
use SproutModules\Karmabunny\Satis\Controllers\WebhookController;

$config['_default'] = PackageController::class . '/index';
$config[] = PackageController::class;
$config[] = WebhookController::class;
