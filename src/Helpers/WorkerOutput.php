<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Sprout\Helpers\Worker;
use Symfony\Component\Console\Output\Output;

/**
 * Send output to the sprout worker log.
 */
class WorkerOutput extends Output
{
    private string $buffer = '';


    /** @inheritdoc */
    protected function doWrite(string $message, bool $newline)
    {
        $this->buffer .= $message;

        if ($newline) {
            Worker::message($this->buffer);
            $this->buffer = '';
        }
    }
}
