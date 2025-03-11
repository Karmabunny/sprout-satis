<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Sprout\Helpers\Json;

/**
 * Inspect satis package contents.
 */
class Inspector
{

    public static function getRoot(): ?array
    {
        static $root;

        if (!$root) {
            $root = Satis::OUTPUT_DIR . '/packages.json';
            $root = @file_get_contents($root);

            if ($root === false) {
                return null;
            }

            $root = Json::decode($root);
        }

        return $root;
    }


    public static function getAllPackages(): array
    {
        $root = self::getRoot();

        if ($root === null) {
            return [];
        }

        return $root['available-packages'];

        // foreach ($root['includes'] ?? [] as $key => $include) {
        //     $include = Satis::OUTPUT_DIR . '/' . $key;
        //     $include = file_get_contents($include);
        //     $include = json_decode($include, true);

        //     $root['packages'] = array_merge($root['packages'], $include['packages'] ?? []);
        // }

        // return $root;
    }


    public static function getPackageReleases(string $name): ?array
    {
        $root = self::getRoot();

        if ($root === null) {
            return null;
        }

        $path = Satis::OUTPUT_DIR . str_replace('%package%', $name, $root['metadata-url']);

        $package = [];
        $dev = [];

        $blob = @file_get_contents($path);
        if ($blob) {
            $json = Json::decode($blob);
            $package = $json['packages'][$name] ?? [];
        }

        $path = str_replace('.json', '~dev.json', $path);
        $blob = @file_get_contents($path);
        if ($blob) {
            $json = Json::decode($blob);
            $dev = $json['packages'][$name] ?? [];
        }

        if (!$package and !$dev) {
            return null;
        }

        usort($package, function($a, $b) {
            return -1 * version_compare($a['version_normalized'], $b['version_normalized']);
        });

        return array_merge($package, $dev);
    }


    public static function getPackageVersions(array $releases): array
    {
        $versions = [];

        foreach ($releases as $release) {
            $key = $release['version_normalized'];
            $versions[$key] = $release['version'];
        }

        return $versions;
    }

}
