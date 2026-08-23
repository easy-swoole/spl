<?php

namespace EasySwoole\Spl\Attribute;


use EasySwoole\Spl\AbstractInterface\ConvertBeanInterface;
use EasySwoole\Spl\SplBean;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ConvertBean
{
    public bool $allowNull = false;

    public bool $iscConvert2SplBean;

    function __construct(
        public string $className,
        public bool $createObjectWhenNull = false
    ){
        $ref = new \ReflectionClass($this->className);
        if(!$ref->isSubclassOf(SplBean::class)){
            if(!$ref->isSubclassOf(ConvertBeanInterface::class)){
                throw new \Exception("{$this->className} not subclass of ".SplBean::class .' or '.(ConvertBeanInterface::class));
            }
            $this->iscConvert2SplBean = false;
        }else{
            $this->iscConvert2SplBean = true;
        }
    }
}