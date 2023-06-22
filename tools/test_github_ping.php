<?php

$url = 'http://kbpackages.gwilyn.bunnysites.com/hooks/github';
$secret = 'KH9ZbaGp7XUtlg1kTFZjClUYt3USf5MY';

$body = [
    'repository' => [
        'full_name' => 'karmabunny/auth',
        'ssh_url' => 'git@github.com:karmabunny/kbauth.git',
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
            'x-github-event: ping',
            'content-type: application/json',
        ],
        'content' => $json,
    ],
]);

$json = file_get_contents($url, false, $context);
$json = json_encode(json_decode($json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo $json;
echo "\n";
