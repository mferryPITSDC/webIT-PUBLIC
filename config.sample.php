<?php

/**
 * WebIT site runtime — base configuration.
 *
 * This is the SAME for every site. There is nothing site-specific here: a
 * site's identity, content and credentials are all bound at pairing time
 * (setup.php) and written to storage/site.local.php (gitignored), which is
 * merged over this file by lib/config.php.
 *
 * You normally never edit this file — run /setup.php instead.
 */

return [
    'api' => [
        'base'   => 'https://api.webIT.pitsdc.net', // stable platform endpoint
        'key'    => '',  // set at pairing (stored in storage/site.local.php)
        'secret' => '',  // set at pairing
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'CHANGE_ME_content_db', // set at pairing
        'user' => 'CHANGE_ME_user',
        'pass' => 'CHANGE_ME_pass',
    ],
    'site' => [
        'id'            => 0,   // set at pairing (from the claim response)
        'name'          => 'My Website',
        'public_domain' => '',
        'reseller'      => ['id' => 0, 'name' => ''],
    ],
];
