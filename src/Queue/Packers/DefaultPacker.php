<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/8/27
 * Time: 上午9:51
 */

namespace Lin\Swoole\Queue\Packers;


class DefaultPacker implements PackerInterface
{
    /**
     * @param mixed $data
     * @return mixed|string
     */
    public function pack($data)
    {
        return serialize($data);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function unpack($data)
    {
        return unserialize($data);
    }
}