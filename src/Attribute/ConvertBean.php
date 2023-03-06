<?php

namespace EasySwoole\Spl\Attribute;


use EasySwoole\Spl\SplBean;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ConvertBean
{
    private bool $allowNull = false;


    function __construct(
        public string $className
    ){
        $ref = new \ReflectionClass($this->className);
        if(!$ref->isSubclassOf(SplBean::class)){
            throw new \Exception("{$this->className} not subclass of ".SplBean::class);
        }
    }

    /**
     * @return bool
     */
    public function isAllowNull(): bool
    {
        return $this->allowNull;
    }

    /**
     * @param bool $allowNull
     */
    public function setAllowNull(bool $allowNull): void
    {
        $this->allowNull = $allowNull;
    }

}