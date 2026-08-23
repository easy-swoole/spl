<?php

namespace EasySwoole\Spl\Attribute;


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
            $toObject = $ref->getMethod('toObject');
            if(!$toObject->isStatic()){
                throw new \Exception("{$this->className} toObject() method must be static");
            }
            $toValue = $ref->getMethod('toValue');
            if((!$toObject) || (!$toValue)){
                throw new \Exception("{$this->className} not subclass of ".SplBean::class .' or not has toObject and toValue method');
            }
            $this->iscConvert2SplBean = false;
        }else{
            $this->iscConvert2SplBean = true;
        }
    }
}