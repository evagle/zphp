<?php

    return array(
        'server_mode' => (PHP_SAPI === 'cli') ? 'Cli' : 'Http',
        'project_name'=>'zphp',
        'pdo'=>array(
            'dns'=>'mysql:host=localhost;port=3306',
            'user'=>'zphp',
            'pass'=>'zphp',
            'dbname'=>'zphp',
            'chartset'=>'UTF8',
        ),
        'storage'=>array(
            'RL'=>array(
                'name'=>'master',
                'pconnect'=>true,
                'host'=>'127.0.0.1',
                'port'=>6379,
                'timeout'=>5
            )
        ),
        'cache'=>array(
            'Yac'=>array()
        ),
        'rank'=>array(
            'Redis'=>array(
                'name'=>'rank',
                'pconnect'=>true,
                'host'=>'127.0.0.1',
                'port'=>6379,
                'timeout'=>5
            )
        ),
        'queue'=>array(
            'Redis'=>array(
                'name'=>'queue',
                'pconnect'=>true,
                'host'=>'127.0.0.1',
                'port'=>6379,
                'timeout'=>5
            )
        ),
    );
