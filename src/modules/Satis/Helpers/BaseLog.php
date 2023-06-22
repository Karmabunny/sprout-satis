<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use karmabunny\pdb\Pdb;
use Kohana;
use Sprout\Helpers\Request;
use Throwable;

/**
 * Logging for things.
 *
 * The table must have the following columns:
 *  - id
 *  - date_added
 *  - success
 *  - error
 */
abstract class BaseLog
{

    /** @var int */
    public $id;


    /** @var Pdb */
    protected Pdb $pdb;


    /**
     * Start a log session.
     *
     * @param array $data
     * @return void
     */
    protected function __construct(array $data)
    {
        $data['date_added'] ??= Pdb::now();
        $data['ip_address'] ??= bin2hex(inet_pton(Request::userIp()));
        $data['error'] ??= 'Fatal';
        $data['success'] ??= 0;

        $table = static::getTableName();

        try {
            $this->pdb = \Sprout\Helpers\Pdb::getInstance();
            $this->id = $this->pdb->insert($table, $data);
        } catch (Throwable $error) {
            Kohana::logException($error);
            $this->id = 0;
        }
    }


    /**
     * The table name for storing logs.
     *
     * @return string
     */
    public static abstract function getTableName(): string;



    /**
     * Record this log as a success.
     *
     * This terminates the log. No further changes can be made.
     *
     * @return void
     */
    public function success()
    {
        if (!$this->id) return;

        $table = static::getTableName();
        $data = ['success' => 1, 'error' => null];
        $conditions = ['id' => $this->id];

        $this->pdb->update($table, $data, $conditions);
        $this->id = 0;
    }


    /**
     * Record this log as a failure, with a reason.
     *
     * @param string $reason
     * @return void
     */
    public function error(string $reason)
    {
        if (!$this->id) return;

        try {
            $table = static::getTableName();
            $data = ['success' => 0, 'error' => $reason];
            $conditions = ['id' => $this->id];

            $this->pdb->update($table, $data, $conditions);

        } catch (Throwable $error) {
            Kohana::logException($error);
        }

        $this->id = 0;
    }


    /**
     * Clean old records.
     *
     * @return void
     */
    protected function clean()
    {
        if (!$this->id) return;

        if ($this->id % rand(500, 1500) == 0) {
            $table = static::getTableName();
            $conditions = [
                ['date_added', '<', date('Y-m-d', strtotime('-2 weeks'))],
            ];

            $this->pdb->delete($table, $conditions);
        }
    }
}
