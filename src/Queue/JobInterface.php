<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/6/2
 * Time: 上午11:32
 */

namespace Lin\Swoole\Queue;


interface JobInterface
{
    public function handle();
}