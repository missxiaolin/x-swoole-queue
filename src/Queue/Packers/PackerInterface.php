<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/8/27
 * Time: 上午9:52
 */

namespace Lin\Swoole\Queue\Packers;


interface PackerInterface
{
    /**
     * Pack data
     *
     * @param mixed $data
     * @return mixed
     */
    public function pack($data);

    /**
     * Unpack data
     *
     * @param mixed $data
     * @return mixed
     */
    public function unpack($data);
}