<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Models;

use Sprout\Helpers\Model;
use Sprout\Helpers\Pdb;

/**
 * Package record.
 */
class Package extends Model
{

    public static function getTableName(): string
    {
        return 'packages';
    }


    /** @var bool */
    public $active;

    /** @var string */
    public $date_added;

    /** @var string */
    public $date_modified;

    /** @var string */
    public $name;

    /** @var string */
    public $repo_url;

    /** @var string */
    public $webhook_token;

    /** @var string|null */
    public $last_build_time;

    /** @var bool */
    public $build_success;

    /** @var int|null */
    public $worker_id;


    public function setWorker(int $id)
    {
        $pdb = static::getConnection();
        $table = static::getTableName();
        $pdb->update($table, ['worker_id' => $id], ['id' => $this->id]);
    }


    public function updateBuildTime()
    {
        $pdb = static::getConnection();
        $table = static::getTableName();
        $pdb->update($table, ['last_build_time' => $pdb->now()], ['id' => $this->id]);
    }


    public function isBuilding(): bool
    {
        if (!$this->worker_id) {
            return false;
        }

        // TODO this could be merged into the worker helper.
        // Particularly the PID bits.
        $worker = Pdb::find('worker_jobs', ['id' => $this->worker_id])
            ->select('status', 'pid', 'date_added')
            ->throw(false)
            ->one();

        if (!$worker) {
            return false;
        }

        if ($worker['status'] === 'Failed') {
            return false;
        }

        // It's a zombie worker job! (probably)
        if ($worker['date_added'] < date('Y-m-d H:i:s', strtotime('-1 hour'))) {
            return false;
        }

        // No really, is it running?
        if ($worker['pid'] and posix_getpgid($worker['pid']) !== false) {
            return true;
        }

        return false;
    }
}
