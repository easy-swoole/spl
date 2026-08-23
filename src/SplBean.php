<?php

namespace EasySwoole\Spl;



use EasySwoole\Spl\Attribute\ConvertBean;

class SplBean implements \JsonSerializable
{
    const FILTER_NOT_NULL = 1;
    const FILTER_NOT_EMPTY = 2;
    const FILTER_NULL = 3;
    const FILTER_EMPTY = 4;

    private array|null $properties = null;

    private array $convertMap = [];


    public function __construct(?array $data = null)
    {
        $this->allProperty($data);
        if($data){
            $this->restore($data);
        }
        $this->initialize();
    }

    final public function allProperty(array|null $data = null): array
    {
        if($this->properties == null){
            $this->properties = [];
            $class = new \ReflectionClass($this);
            $list = $class->getProperties(
                \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED
            );
            foreach ($list as $property) {
                if ($property->isStatic()) {
                    continue;
                }
                $convertBean = null;
                $all = $property->getAttributes();
                foreach ($all as $sub){
                    $name = $sub->getName();
                    $ref = new \ReflectionClass($name);
                    if($ref->isSubclassOf(ConvertBean::class) || ($name == ConvertBean::class)){
                        $convertBean = new $name(...$sub->getArguments());
                        break;
                    }
                }
                /**
                 *@var ConvertBean $convertBean
                 */
                if($convertBean){
                    $types = $property->getType();
                    if($types){
                        $convertBean->allowNull = $types->allowsNull();
                    }
                    if(!isset($data[$property->getName()])){
                        if($convertBean->allowNull){
                            $this->{$property->name} = null;
                        }else{
                            if(!$convertBean->createObjectWhenNull){
                                throw new \Exception("data for property {$property->getName()} at class ".static::class." cannot be null");
                            }else{
                                if($convertBean->iscConvert2SplBean){
                                    $this->{$property->name} = new $convertBean->className;
                                }else{
                                    $this->{$property->name} = call_user_func([$convertBean->className,'toObject'],null);
                                }
                            }
                        }
                    }
                    $this->properties[$property->name] = true;
                    $this->convertMap[$property->name] = $convertBean;
                }else{
                    if($property->getDefaultValue() !== null){
                        $this->{$property->name} = $property->getDefaultValue();
                    }else{
                        $types = $property->getType();
                        if($types){
                            if($types->allowsNull()){
                                $this->{$property->name} = null;
                            }
                        }
                    }
                    $this->properties[$property->name] = true;
                }
            }
        }

        return array_keys($this->properties);
    }

    function toArray(int|callable|null $filter = null): array
    {
        $data = [];
        foreach ($this->properties as $key => $property){
            $temp = new \ReflectionProperty(static::class,$key);
            if($temp->isInitialized($this)){
                if(is_object($this->{$key})){
                    if($this->{$key} instanceof SplBean){
                        $data[$key] = $this->{$key}->jsonSerialize();
                    }else{
                        if(method_exists($this->{$key},'toValue')){
                            $data[$key] = $this->{$key}->toValue();
                        }else{
                            $data[$key] = $this->{$key};
                        }
                    }
                }else{
                    $data[$key] = $this->{$key};
                }

            }else{
                $data[$key] = null;
            }
        }
        if ($filter === self::FILTER_NOT_NULL) {
            return array_filter($data, function ($val) {
                return !is_null($val);
            });
        } else if ($filter === self::FILTER_NOT_EMPTY) {
            return array_filter($data, function ($val) {
                return !empty($val);
            });
        } else if ($filter === self::FILTER_NULL) {
            return array_filter($data, function ($val) {
                return is_null($val);
            });
        } else if ($filter === self::FILTER_EMPTY) {
            return array_filter($data, function ($val) {
                return empty($val);
            });
        } else if (is_callable($filter)) {
            return array_filter($data, $filter);
        }
        return $data;
    }

    final public function getProperty($name)
    {
        if (key_exists($name,$this->properties)) {
            return $this->$name;
        } else {
            return null;
        }
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }

    protected function initialize(): void
    {

    }

    public function restore(array $data = [])
    {
        foreach ($this->properties as $key => $property){
            if(key_exists($key,$data)){
                if(isset($this->convertMap[$key])){
                    /** @var ConvertBean $convert */
                    $convert = $this->convertMap[$key];
                    $class = $convert->className;
                    $val = $data[$key];
                    if($convert->iscConvert2SplBean){
                        if(is_array($val)){
                            $this->{$key} = new $class($val);
                        }else if(is_string($val)){
                            $arr = json_decode($val,true);
                            if(is_array($arr)){
                                $this->{$key} = new $class($arr);
                            }else{
                                throw new \Exception("data for property {$key} at class {$class} not a json format");
                            }
                        }elseif(is_object($val) && ($val instanceof $class)){
                            $this->{$key} = $val;
                        }else{
                            if(!empty($val) && (!$convert->allowNull)){
                                throw new \Exception("data for property {$key} at class {$class} not a json format");
                            }
                        }
                    }else{
                        if($val instanceof $class){
                            $this->{$key} = $val;
                        }else{
                            $this->{$key} = call_user_func([$convert->className,'toObject'], $val);
                        }
                    }
                }else{
                    $this->{$key} = $data[$key];
                }
            }
        }
        return $this;
    }
}
