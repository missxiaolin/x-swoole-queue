<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/6/2
 * Time: 下午1:08
 */

namespace Tests\Test\App;


use Lin\Swoole\Queue\JobInterface;


class ExceptionJob implements JobInterface
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        sleep(1);
        throw new \Exception($this->data);
    }
}