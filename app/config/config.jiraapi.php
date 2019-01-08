<?php

return
    [
        'redis'         =>
            [
                'dev'           =>
                    [
                        6421 =>
                            [
                                'type'      => \lib\redis::REDIS_CONFIG_TYPE_IPPORT,
                                'ip'        => '127.0.0.1',
                                'port'      => 6421,
                                'timeout'   => 5,
                            ],
                        6422 =>
                            [
                                'type'      => \lib\redis::REDIS_CONFIG_TYPE_IPPORT,
                                'ip'        => '127.0.0.1',
                                'port'      => 6422,
                                'timeout'   => 5,
                            ],
                    ],
                ]
        ];