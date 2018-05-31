<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/5/31
 * Time: 下午2:27
 */

namespace Lin\Swoole\Common\Redis;

use Predis\Client;

class Redis
{
    protected static $_instance = [];
    protected $redis;

    /**
     * 获取redis单例
     * @param string $host
     * @param null $auth
     * @param int $db
     * @param int $port
     * @param null $uniqid
     * @return mixed|Client
     */
    public static function getInstance($host = '127.0.0.1', $auth = null, $db = 0, $port = 6379, $uniqid = null)
    {
        $key = md5(json_encode([$host, $auth, $db, $port, $uniqid]));

        if (isset(static::$_instance[$key])) {
            return static::$_instance[$key];
        }

        return static::$_instance[$key] = static::getClient($host, $port, $auth, $db);
    }

    /**
     * @param $host
     * @param $port
     * @param $auth
     * @param $db
     * @return Client
     */
    protected static function getClient($host, $port, $auth, $db)
    {
        $redis = new Client([
            'scheme' => 'tcp',
            'host' => $host,
            'port' => $port,
            'auth' => $auth,
            'database' => $db,
        ]);
        return $redis;
    }

}