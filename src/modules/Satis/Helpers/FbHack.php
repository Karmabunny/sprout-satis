<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Sprout\Helpers\Fb;
use Sprout\Helpers\Sprout;

/**
 * Send output to the sprout worker log.
 */
class FbHack
{

    public static function boolean(string $name)
    {
        $data = Fb::getData($name);
        $data =  $data ? 'OK': 'error';
        return <<<EOF
            <pre class="field-input">{$data}</pre>
        EOF;
    }


    public static function date(string $name)
    {
        $data = Fb::getData($name);
        if (!$data) {
            $data = '(empty)';
        }

        return <<<EOF
            <pre class="field-input">{$data}</pre>
        EOF;
    }


    public static function worker(string $name)
    {
        $data = Fb::getData($name);
        $data = (int) $data;

        if (!$data) {
            return <<<EOF
                <div class="field-input">
                    (empty)
                </div>
            EOF;
        }

        return <<<EOF
            <a href="admin/edit/worker_job/{$data}">Job #{$data}</a>
        EOF;
    }


    public static function instructions()
    {
        $url = Sprout::absRoot('https') . 'hooks/github';

        // Instructions for settings up github webhooks.
        return <<<EOF
            <h4>Instructions:</h4>
            <style>
            .-hack-ol {
                line-height: 2;
            }
            .-hack-ol code {
                padding: 3px 6px;
            }
            </style>
            <ol class="-hack-ol">
                <li>
                    Go to your repository settings.
                </li>
                <li>
                    Go to <code>Webhooks</code> and click <code>Add webhook</code>.
                </li>
                <li>
                    Enter this URL: <code>{$url}</code>
                </li>
                <li>
                    Enter the webhook token (above)
                </li>
                <li>
                    Select <code>application/json</code> as the content type.
                </li>
                <li>
                    Select <code>Just the push event</code> as the trigger.
                </li>
                <li>
                    Done!
                </li>
            </ol>
        EOF;
    }
}
