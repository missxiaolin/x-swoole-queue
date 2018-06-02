<?php
// +----------------------------------------------------------------------
// | EnumException.php [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 xiaolin All rights reserved.
// +----------------------------------------------------------------------
// | Author: xiaolin <462441355@qq.com> <https://github.com/missxiaolin>
// +----------------------------------------------------------------------
namespace Tests\Test\App;

use Lin\Swoole\Common\File\File;
use Lin\Swoole\Queue\Task;

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