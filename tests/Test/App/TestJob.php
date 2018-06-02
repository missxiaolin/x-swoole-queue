<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/6/2
 * Time: 下午12:27
 */

namespace Tests\Test\App;


use Lin\Swoole\Common\File\File;
use Lin\Swoole\Queue\JobInterface;

class TestJob implements JobInterface
{
    public $data;

    public $file = TESTS_PATH . '/test.cache';

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        File::getInstance()->put($this->file, $this->data);
    }
}