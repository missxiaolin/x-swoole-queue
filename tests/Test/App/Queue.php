<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/6/13
 * Time: 下午7:27
 */

namespace Tests\Test\App;


use Lin\Swoole\Queue\Job;

class Queue extends Job
{
    protected $maxProcesses = 10;
    
    /**
     * Queue constructor.
     */
    public function __construct()
    {
        $config = include TESTS_PATH . '/_ci/config.php';

        $host = $config['redisHost'];
        $auth = $config['redisAuth'];
        $db = $config['redisDb'];
        $port = $config['redisPort'];

        $this->setRedisConfig($host, $auth, $db, $port);
        $this->setPidPath(TESTS_PATH . '/queue2.pid');
    }
}