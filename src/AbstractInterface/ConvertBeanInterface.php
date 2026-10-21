<?php

namespace EasySwoole\Spl\AbstractInterface;

interface ConvertBeanInterface
{
    public static function toObject(mixed $data): object;

    /** @return mixed */
    public function toValue();
}
