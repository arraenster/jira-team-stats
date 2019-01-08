<?php

return new \Phalcon\Config(
    [
        'jira_auth'   =>
        [
            'host'      => '<hostname>',
            'username'  => '<username>',
            'password'  => '<password>'
        ],
        'application' =>
        [
            'controllersDir' => __DIR__ . '/../../app/controllers/',
            'modelsDir'      => __DIR__ . '/../../app/models/',
            'viewsDir'       => __DIR__ . '/../../app/views/',
            'libraryDir'     => __DIR__ . '/../../app/library/',
            'baseUri'        => '/',
        ]
    ]
);
