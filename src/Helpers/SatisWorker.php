<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Sprout\Helpers\Worker;
use Sprout\Helpers\WorkerJob;

/**
 * A worker to build the satis repository.
 *
 * Provided with an array of repository URLs, only those packages will be be processed.
 */
class SatisWorker extends WorkerJob
{

    public function __construct(public array $filter = [])
    {
    }


    /** @inheritdoc */
    public function getName(): string
    {
        $name = 'Build:';

        if ($this->filter) {
            foreach ($this->filter as $filter) {
                [, $repo] = explode(':', $filter, 2) + ['', ''];
                $name .= ' ' . $repo;
            }
        } else {
            $name .= ' all packages';
        }

        return $name;
    }


    /** @inheritdoc */
    public function run()
    {
        ini_set('memory_limit', '256M');

        $output = new WorkerOutput();

        $packages = Satis::build($output, $this->filter);
        Satis::updatePackages($packages);
    }
}
