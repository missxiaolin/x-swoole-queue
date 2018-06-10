<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/6/10
 * Time: 下午8:49
 */

namespace Tests\Test\App;


use Lin\Swoole\Common\Redis\Redis;
use Lin\Swoole\Queue\JobInterface;

class ManyJob implements JobInterface
{
    public $key = 'test:incr';

    public function handle()
    {
        $config = include TESTS_PATH . '/_ci/config.php';

        $host = $config['redisHost'];
        $auth = $config['redisAuth'];
        $db = $config['redisDb'];
        $port = $config['redisPort'];

        $redis = Redis::getInstance($host, $auth, $db, $port);
        $redis->incr($this->key);
    }
}