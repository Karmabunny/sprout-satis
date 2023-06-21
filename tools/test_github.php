<?php

$url = 'http://kbpackages.ghome.bunnysites.com/hooks/github';
$secret = 'c54hG39NzAHjiAmnLeQNDBI9DY0bYH99';

$body = [
    'repository' => [
        'full_name' => 'karmabunny/sprout3',
    ],
];

$json = json_encode($body);
$signature = hash_hmac('sha256', $json, $secret);

$context = stream_context_create([
    'http' => [
        'ignore_errors' => true,
        'method' => 'POST',
        'header' => [
            'x-hub-signature-256: sha256=' . $signature,
            'x-github-event: push',
            'content-type: application/json',
        ],
        'content' => $json,
    ],
]);

echo file_get_contents($url, false, $context);
echo "\n";
