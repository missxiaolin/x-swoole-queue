<?php
// +----------------------------------------------------------------------
// | EnumException.php [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 xiaolin All rights reserved.
// +----------------------------------------------------------------------
// | Author: xiaolin <462441355@qq.com> <https://github.com/missxiaolin>
// +----------------------------------------------------------------------
require __DIR__ . '/bootstrap.php';

use Tests\Test\App\TestQueue;

$config = include TESTS_PATH . '/_ci/config.php';

$queue = new TestQueue();
$queue->setRedisConfig($config['redisHost'], $config['redisAuth'], $config['redisDb'], $config['redisPort'])
    ->run();
