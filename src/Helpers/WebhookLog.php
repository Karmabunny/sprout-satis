<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Sprout\Helpers\Request;

/**
 * Logging for web hook events.
 */
class WebhookLog extends BaseLog
{

    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'packages_webhook_log';
    }


    /**
     * Create a new log item.
     *
     * @param string $provider
     * @param string $event
     * @return self
     */
    public static function create(string $provider, $event): self
    {
        return new self([
            'provider' => $provider,
            'event' => ((string) $event) ?: 'unknown',
            'headers' => json_encode(Request::getHeaders()),
            'body' => Request::getRawBody(),
        ]);
    }


    /**
     * Record the package ref against the log.
     *
     * This ref is provider specific.
     * Typically it should be the package name or repository url.
     *
     * @param string $ref
     * @return void
     */
    public function setPackage(string $ref)
    {
        if (!$this->id) return;

        $table = static::getTableName();
        $data = ['package_ref' => $ref];
        $conditions = ['id' => $this->id];
        $this->pdb->update($table, $data, $conditions);
    }

}
