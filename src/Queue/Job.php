<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/6/2
 * Time: 上午11:30
 */

namespace Lin\Swoole\Queue;

use Psr\Log\LoggerInterface;
use Exception;

class Job extends Task
{
    // 最大进程数
    protected $maxProcesses = 3;
    // 子进程最大循环处理次数
    protected $processHandleMaxNumber = 10000;
    // 失败的消息
    protected $errorKey = 'swoole:queue:error';
    // 消息队列Redis键值 list lpush添加队列
    protected $queueKey = 'swoole:queue:queue';
    // 延时消息队列的Redis键值 zset
    protected $delayKey = 'swoole:queue:delay';
    // pid地址
    protected $pidPath = TESTS_PATH . '/queue.pid';
    // 日志Handler
    protected $loggerHandler;

    /**
     * @param $key
     * @return $this
     */
    public function setQueueKey($key)
    {
        $this->queueKey = $key;
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function setDelaykey($key)
    {
        $this->delayKey = $key;
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function setErrorKey($key)
    {
        $this->errorKey = $key;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPidPath($path)
    {
        $this->pidPath = $path;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLoggerHandler(LoggerInterface $logger)
    {
        $this->loggerHandler = $logger;
        return $this;
    }

    /**
     * @param $recv
     * @return mixed|void
     */
    protected function handle($recv)
    {
        try {
            $obj = unserialize($recv);
            if ($obj instanceof JobInterface) {
                $name = get_class($obj);
                $date = date('Y-m-d H:i:s');
                dump("[{$date}] Processing: {$name}");
                // 处理消息
                $obj->handle();
                $date = date('Y-m-d H:i:s');
                dump("[{$date}] Processed: {$name}");
            }
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            dump("[{$date}] Failed: {$name}");

            // 推送失败的消息对失败队列
            $redis = static::redisChildClient('job');
            $redis->lpush($this->errorKey, $recv);
        }
    }

    /**
     * @desc   重载失败的Job
     * @author limx
     */
    public function reloadErrorJobs()
    {
        $redis = $this->redisChildClient('job');
        while ($data = $redis->rpop($this->errorKey)) {
            $redis->lpush($this->queueKey, $data);
        }
        dump("失败的脚本已重新载入消息队列！");
    }

    /**
     * @desc   删除所有失败的Job
     * @author limx
     */
    public function flushErrorJobs()
    {
        $redis = $this->redisChildClient('job');
        $redis->del($this->errorKey);
        dump('失败的脚本已被清除！');
    }
}