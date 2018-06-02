<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/5/31
 * Time: 上午10:47
 */

namespace Tests\Test;

use Lin\Swoole\Common\File\File;
use Tests\Test\App\TestJob;
use Tests\TestCase;

class BaseTest extends TestCase
{
    public function testSwooleCase()
    {
        $this->assertTrue(extension_loaded('swoole'));
    }

    public function testSwooleQueueTask()
    {
        File::getInstance()->put($this->file, 'init');
        $data = file_get_contents($this->file);
        $this->assertEquals('init', $data);
        $this->redis->lPush('test:queue:queue', 'xxxx');
        sleep(2);
        $data = file_get_contents($this->file);
        $this->assertEquals('upgrade', $data);
    }

    public function testSwooleQueueJob()
    {
        $data = file_get_contents($this->file);
        $this->assertEquals('init', $data);
        $job = new TestJob('upgrade by test job!');
        $this->redis->lPush('swoole:queue:queue', serialize($job));
        sleep(2);
        $data = file_get_contents($this->file);
        $this->assertEquals('upgrade by test job!', $data);
    }
}