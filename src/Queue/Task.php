<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/5/31
 * Time: 下午1:52
 */

namespace Lin\Swoole\Queue;

use Lin\Swoole\Common\File\File;
use Lin\Swoole\Common\Redis\Redis;
use swoole_process;

abstract class Task
{
    /**
     * 最大进程数
     * @var int
     */
    protected $maxProcesses = 10;

    /**
     * 当前进程数
     * @var int
     */
    protected $process = 0;

    /**
     * 消息队列Redis键值 list lpush添加队列
     * @var string
     */
    protected $queueKey = '';

    /**
     * 延时消息队列的Redis键值 zset
     * @var string
     */
    protected $delayKey = '';

    /**
     * 子进程数到达最大值时的等待时间
     * @var int
     */
    protected $waitTime = 1;

    /**
     * pid地址
     * @var string
     */
    protected $pidPath;

    /**
     * 主进程PID
     * @var int
     */
    protected $pid;

    /**
     * 子进程最大循环处理次数
     * @var null
     */
    protected $processHandleMaxNumber = 10000;

    /**
     * Redis Host
     * @var string
     */
    protected $redisHost = '127.0.0.1';
    /**
     * Redis Auth
     * @var null
     */
    protected $redisAuth = null;

    /**
     * Redis DB
     * @var int
     */
    protected $redisDb = 0;

    /**
     * Redis Port
     * @var int
     */
    protected $redisPort = 6379;

    /**
     * @desc   监听消息队列，并分发给子进程进行处理
     * @author xl
     * @throws QueueException
     */
    public function run()
    {
        if (version_compare(PHP_VERSION, '7.1', ">=")) {
            pcntl_async_signals(true);
        } else {
            declare(ticks=1);
        }

        // 验证必要参数
        $this->verify();

        $this->pid = posix_getpid();
        // 写入PID
        File::getInstance()->put($this->pidPath, $this->pid);

        // install signal handler for dead kids
        pcntl_signal(SIGCHLD, [$this, "signalHandler"]);
        set_time_limit(0);

        // 实例化Redis实例
        $redis = $this->redisClient();
        while (true) {
            // 等待
            sleep($this->waitTime);

            // 监听延时队列
            if (!empty($this->delayKey) && $delay_data = $redis->zrangebyscore($this->delayKey, 0, time())) {
                foreach ($delay_data as $data) {
                    // 把可以执行的消息压入队列中
                    $redis->lpush($this->queueKey, $data);
                    $redis->zrem($this->delayKey, $data);
                }
            }
            // 监听消息队列
            if ($this->process < $this->maxProcesses) {
                // 获取消息列表数量
                $len = $redis->llen($this->queueKey);
                if ($len === 0) {
                    continue;
                }

                // fork子进程处理消息
                $process = new swoole_process([$this, 'task']);
                $pid = $process->start();
                if ($pid !== false) {
                    $this->process++;
                }
            }
        }
    }

    /**
     * @desc   验证消息队列必要参数是否存在
     * @author xl
     * @throws QueueException
     */
    protected function verify()
    {
        if (!extension_loaded('swoole')) {
            throw new QueueException('The swoole extension is not installed');
        }

        if (empty($this->queueKey)) {
            throw new QueueException('Please rewrite the queueKey');
        }

        if (empty($this->pidPath)) {
            throw new QueueException('Please rewrite the pidPath');
        }
    }

    /**
     * @desc   设置Redis配置
     * @author xl
     * @param $host
     * @param $auth
     * @param $db
     * @param $port
     * @return mixed
     */
    public function setRedisConfig($host, $auth, $db, $port)
    {
        $this->redisHost = $host;
        $this->redisAuth = $auth;
        $this->redisDb = $db;
        $this->redisPort = $port;
        return $this;
    }

    /**
     * @desc   返回redis实例
     * @author xl
     * @return mixed
     */
    protected function redisClient()
    {
        return Redis::getInstance(
            $this->redisHost,
            $this->redisAuth,
            $this->redisDb,
            $this->redisPort,
            'child_' . uniqid()
        );
    }

    /**
     * 子进程redis实例
     * @param null $uniqid
     * @return mixed|\Predis\Client
     */
    protected function redisChildClient($uniqid = null)
    {
        if (empty($uniqid)) {
            $uniqid = uniqid();
        }

        return Redis::getInstance(
            $this->redisHost,
            $this->redisAuth,
            $this->redisDb,
            $this->redisPort,
            'child_' . $uniqid
        );
    }

    /**
     * @desc   子进程
     * @author xl
     * @param swoole_process $worker
     */
    public function task(swoole_process $worker)
    {
        $redis = $this->redisChildClient();
        $number = 0;
        while (true) {
            // 判断主进程是否退出
            if (!swoole_process::kill($this->pid, 0)) {
                $worker->exit();
            }

            // 当子进程处理次数高于一个临界值后，释放进程
            if (isset($this->processHandleMaxNumber) && $this->processHandleMaxNumber < (++$number)) {
                break;
            }


            // 无任务时,阻塞等待
            $list = $redis->brpop($this->queueKey, 3);
            if (!$list) {
                break;
            }

            list($key, $data) = $list;
            if ($key != $this->queueKey) {
                // 消息队列KEY值不匹配
                continue;
            }
            if (isset($data)) {
                $this->handle($data);
            }
        }
    }

    /**
     * @desc   主进程中操作数据
     * @tip    主进程中不能实例化DB类等存在连接池的对象，因为在子进程释放后，
     *         会把连接池释放掉，导致主进程出现问题
     * @author xl
     * @param $data 消息队列中的数据
     * @return mixed 返回给子进程的数据
     */
    protected function rewrite($data)
    {
        return $data;
    }

    /**
     * @desc   消息队列 业务逻辑处理
     * @author xl
     * @param $recv
     * @return mixed
     */
    abstract protected function handle($recv);

    /**
     * @desc   信号处理方法 回收已经dead的子进程
     * @author xl
     * @param $signo
     */
    private function signalHandler($signo)
    {
        switch ($signo) {
            case SIGCHLD:
                while (swoole_process::wait(false)) {
                    $this->process--;
                }

            // no break
            default:
                break;
        }
    }
}