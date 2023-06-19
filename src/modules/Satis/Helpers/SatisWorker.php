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
    public function run(array $packages = [])
    {
        $output = new WorkerOutput();

        $ok = Satis::build($output, $packages);
        Satis::updatePackages($packages, $ok);

        Worker::success();
    }
}
