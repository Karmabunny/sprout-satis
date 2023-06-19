<?php

if (!IN_PRODUCTION) {
    $config['operators'] = [
        'gwilynlocal' => ['uid' => 100000, 'hash' => '$2y$12$ApRl29HjlZAxYpD/IUzCzefOwQDQ2xRCCatgp7HTF29lFbZ7Clx42', 'salt' => 'S3Za'],
    ];
}
