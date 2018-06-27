# x-swoole-queue

### 执行测试

~~~
php run tests/queue.php
vendor/bin/phpunit
~~~

### 使用

~~~
class TestQueue extends Task
{
    // 消息队列Redis键值 list lpush添加队列
    protected $queueKey = 'test:queue:queue';
    // 延时消息队列的Redis键值 zset
    protected $delayKey = 'test:queue:delay';
    // pid地址
    protected $pidPath = TESTS_PATH . '/queue.pid';

    public $file = TESTS_PATH . '/test.cache';

    /**
     * @param $recv
     * @return mixed|void
     */
    protected function handle($recv)
    {
        File::getInstance()->put($this->file, 'upgrade');
    }
}

$queue = new TestQueue();
$queue->setRedisConfig(127.0.0.1, 'xiaolin', 1, 8366)
    ->run();
    
$this->redis->lPush('test:queue:queue', 'xxxx');
~~~