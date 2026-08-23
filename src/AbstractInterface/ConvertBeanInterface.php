<?php

namespace EasySwoole\Spl\AbstractInterface;

interface ConvertBeanInterface
{
    public static function toObject(mixed $data):object;

    public function toValue();
}