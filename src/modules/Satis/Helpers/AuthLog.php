<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Sprout\Helpers\Request;
use Sprout\Helpers\Router;

/**
 * Logging for auth events.
 */
class AuthLog extends BaseLog
{

    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'sites_auth_log';
    }


    /**
     * Create a new log item.
     *
     * @param string $user
     * @param string $pass
     * @return self
     */
    public static function create(string $user, string $pass): self
    {
        return new self([
            'username' => $user,
            'password_hash' => sha1($pass),
            'path' => Router::$current_uri,
            'ip_address' => bin2hex(inet_pton(Request::userIp())),
        ]);
    }
}
