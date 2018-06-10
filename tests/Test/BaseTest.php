<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/5/31
 * Time: 上午10:47
 */

namespace Tests\Test;

use Lin\Swoole\Common\File\File;
use Tests\Test\App\ExceptionJob;
use Tests\Test\App\ManyJob;
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

    public function testExceptionJob()
    {
        $job = new ExceptionJob('hi, exception');
        $this->redis->del('swoole:queue:error');
        $this->redis->lPush('swoole:queue:queue', serialize($job));

        sleep(2);
        $this->assertTrue($this->redis->lLen('swoole:queue:error') === 1);
    }

    public function testManyJob()
    {
        $this->redis->del('test:incr');

        for ($i = 0; $i < 10; $i++) {
            $job = new ManyJob();
            $this->redis->lPush('swoole:queue:queue', serialize($job));
        }

        sleep(6);
        $this->assertEquals(10, $this->redis->get('test:incr'));
    }
}