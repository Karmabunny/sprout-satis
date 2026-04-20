<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

/**
 * Asset archives.
 */
class Assets
{

    const OUTPUT_DIR = STORAGE_PATH . 'assets';


    public static function exists(string $package, string $version): bool
    {
        $path = self::OUTPUT_DIR . "/{$package}/{$version}.zip";
        return is_file($path);
    }


    public static function delete(string $package, string $version)
    {
        $path = self::OUTPUT_DIR . "/{$package}/{$version}.zip";
        @unlink($path);
    }

}
