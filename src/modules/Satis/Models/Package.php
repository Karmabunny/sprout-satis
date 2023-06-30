<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Models;

use Sprout\Helpers\Model;
use Sprout\Helpers\Pdb;
use SproutModules\Karmabunny\Satis\Helpers\Inspector;

/**
 * Package record.
 */
class Package extends Model
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
    public $repo_url;

    /** @var string */
    public $webhook_token;

    /** @var string|null */
    public $webhook_valid_time;

    /** @var string|null */
    public $last_build_time;

    /** @var bool */
    public $build_success;

    /** @var int|null */
    public $worker_id;


    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'packages';
    }


    /**
     * Record a worker job ID against this package.
     *
     * This is used to prevent simultaneous builds of the same package.
     *
     * @param int $id
     * @return void
     */
    public function setWorker(int $id)
    {
        $pdb = static::getConnection();
        $table = static::getTableName();
        $pdb->update($table, ['worker_id' => $id], ['id' => $this->id]);

        $this->worker_id = $id;
    }


    /**
     * Update the last build time.
     *
     * @return void
     */
    public function updateBuildTime()
    {
        $pdb = static::getConnection();
        $table = static::getTableName();

        $now = $pdb->now();
        $pdb->update($table, ['last_build_time' => $now], ['id' => $this->id]);
        $this->last_build_time = $now;
    }


    /**
     * Record that the webhook has worked!
     *
     * This is just for debugging. It has no functional purpose.
     *
     * @param int $id
     * @return void
     */
    public function setValidWebhook()
    {
        $pdb = static::getConnection();
        $table = static::getTableName();

        $now = $pdb->now();
        $pdb->update($table, ['webhook_valid_time' => $now], ['id' => $this->id]);
        $this->webhook_valid_time = $now;
    }


    /**
     * Is there a worker already building this package?
     *
     * @return bool
     */
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


    public function getReleases(): array
    {
        return $this->getCachedValue('releases', function() {
            return Inspector::getPackageReleases($this->name) ?? [];
        });
    }


    public function getVersions(): array
    {
        $releases = $this->getReleases();
        return Inspector::getPackageVersions($releases);
    }
}
