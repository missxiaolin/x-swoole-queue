<?php
// +----------------------------------------------------------------------
// | EnumException.php [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 xiaolin All rights reserved.
// +----------------------------------------------------------------------
// | Author: xiaolin <462441355@qq.com> <https://github.com/missxiaolin>
// +----------------------------------------------------------------------
namespace Tests\Test\App;

use Lin\Swoole\Queue\Task;

class TestQueue extends Task
{
    // 消息队列Redis键值 list lpush添加队列
    protected $queueKey = 'swoole:queue:queue';
    // 延时消息队列的Redis键值 zset
    protected $delayKey = 'swoole:queue:delay';
    // pid地址
    protected $pidPath = TESTS_PATH . '/queue.pid';

    protected function handle($recv)
    {
        echo $recv;
    }
}