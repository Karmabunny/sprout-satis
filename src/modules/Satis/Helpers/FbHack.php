<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Kohana;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
use Sprout\Helpers\Json;
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


    public static function versions(): string
    {
        $name = Fb::getData('name');
        if (!$name) return ' -- no versions -- ';

        $releases = Inspector::getPackageReleases($name);
        if (!$releases) return ' -- no versions -- ';

        $versions = Inspector::getPackageVersions($releases);

        ob_start();

        echo "<div style='display: flex; flex-wrap: wrap;'>";

        foreach ($versions as $version) {
            $version = Enc::html($version);
            echo "<code style='margin:0'>{$version}</code>";
        }

        echo "</div>";

        return ob_get_clean();
    }


    /**
     * Instructions for settings up github webhooks.
     *
     * @return string
     */
    public static function repoInstructions(): string
    {
        $url = Sprout::absRoot('https') . 'hooks/github';

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


    /**
     * Instructions for adding authentication to a repository.
     *
     * @return string
     */
    public static function siteInstructions(): string
    {
        $domain = $_SERVER['HTTP_HOST'];

        $example_repo = Json::encode([
            'repositories' => [
                [
                    'type' => 'composer',
                    'url' => "https://{$domain}",
                ],
            ],
        ], true);

        $example_auth = Json::encode([
            'http-basic' => [
                $domain => [
                    'username' => '...',
                    'password' => '...',
                ],
            ],
        ], true);

        return <<<EOF
            <h4>Installation:</h4>
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
                    <p>Add the following to your project <code>composer.json</code></p>
                    <pre>{$example_repo}</pre>
                </li>
                <li>
                    <p>Run the following command:</p>
                    <pre>composer config http-basic.{$domain} <username> <password></pre>
                    <p>This produces a file that looks like:</p>
                    <pre>{$example_auth}</pre>
                </li>
                <li>
                    Done!
                </li>
            </ol>
        EOF;
    }
}
