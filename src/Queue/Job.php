<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/6/2
 * Time: 上午11:30
 */

namespace Lin\Swoole\Queue;

use Lin\Swoole\Queue\Packers\DefaultPacker;
use Lin\Swoole\Queue\Packers\PackerInterface;
use Psr\Log\LoggerInterface;
use Exception;

class Job extends Task
{
    /**
     * 最大进程数
     * @var int
     */
    protected $maxProcesses = 3;

    /**
     * 子进程最大循环处理次数
     * @var int
     */
    protected $processHandleMaxNumber = 10000;

    /**
     * 失败的消息
     * @var string
     */
    protected $errorKey = 'swoole:queue:error';

    /**
     * 消息队列Redis键值 list lpush添加队列
     * @var string
     */
    protected $queueKey = 'swoole:queue:queue';

    /**
     * 延时消息队列的Redis键值 zset
     * @var string
     */
    protected $delayKey = 'swoole:queue:delay';

    /**
     * pid地址
     * @var string
     */
    protected $pidPath = TESTS_PATH . '/queue.pid';

    /**
     * 日志Handler
     * @var object
     */
    protected $loggerHandler;

    /**
     * 当前redis 实例
     * @var object
     */
    protected $redis;

    /**
     * 打包器
     * @var object
     */
    protected $packer;

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
            $packer = $this->getPacker();
            $obj = $packer->unpack($recv);
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
            $redis = static::redisChildClient();
            $redis->lpush($this->errorKey, $recv);
        }
    }

    /**
     * @desc   重载失败的Job
     * @author xl
     */
    public function reloadErrorJobs()
    {
        $redis = $this->getRedisChildClient();
        $count = 0;
        while ($data = $redis->rpoplpush($this->errorKey, $this->queueKey)) {
            $count++;
        }

        return $count;
    }

    /**
     * @desc   删除所有失败的Job
     * @author xl
     */
    public function flushErrorJobs()
    {
        $redis = $this->redisChildClient();
        return $redis->del($this->errorKey);
    }

    /**
     * @return mixed|\Predis\Client
     */
    public function getRedisChildClient()
    {
        if (isset($this->redis) && $this->redis instanceof Redis) {
            return $this->redis;
        }

        return $this->redis = $this->redisChildClient('job');
    }

    /**
     * @return DefaultPacker
     */
    public function getPacker()
    {
        if (isset($this->packer) && $this->packer instanceof PackerInterface) {
            return $this->packer;
        }
        return $this->packer = new DefaultPacker();
    }

    /**
     * @param JobInterface $job
     * @return int
     */
    public function push(JobInterface $job)
    {
        $redis = $this->getRedisChildClient();
        $packer = $this->getPacker();
        return $redis->lpush($this->queueKey, $packer->pack($job));
    }

    /**
     * @param JobInterface $job
     * @param int $time
     * @return int
     */
    public function delay(JobInterface $job, $time = 0)
    {
        if (empty($time)) {
            return $this->push($job);
        }

        $redis = $this->getRedisChildClient();
        $packer = $this->getPacker();
        return $redis->zAdd($this->delayKey, time() + $time, $packer->pack($job));
    }

    /**
     * 查询失败的消息数
     * @return int
     */
    public function countErrorJobs()
    {
        $redis = $this->getRedisChildClient();
        return $redis->lLen($this->errorKey);
    }

}