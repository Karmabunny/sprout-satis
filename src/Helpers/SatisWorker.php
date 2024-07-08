<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Sprout\Helpers\Worker;
use Sprout\Helpers\WorkerBase;

/**
 * A worker to build the satis repository.
 *
 * Provided with an array of repository URLs, only those packages will be be processed.
 */
class SatisWorker extends WorkerBase
{
    protected $job_name = 'Build';


    /** @inheritdoc */
    public function run(array $filter = [])
    {
        ini_set('memory_limit', '256M');

        $output = new WorkerOutput();

        $packages = Satis::build($output, $filter);
        Satis::updatePackages($packages);

        Worker::success();
    }
}
