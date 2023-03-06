<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/22
 * Time: 下午2:53
 */

namespace EasySwoole\Spl;



use EasySwoole\Spl\Attribute\ConvertBean;

class SplBean implements \JsonSerializable
{
    const FILTER_NOT_NULL = 1;
    const FILTER_NOT_EMPTY = 2;
    const FILTER_NULL = 3;
    const FILTER_EMPTY = 4;

    private array|null $properties = null;


    public function __construct(?array $data = null)
    {
        $this->allProperty();
        if($data){
            $this->restore($data);
        }
        $this->initialize();
    }

    final public function allProperty(): array
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
                $convertBean = $property->getAttributes(ConvertBean::class);
                if($convertBean){
                    $convertBean = new ConvertBean(...$convertBean[0]->getArguments());
                    $types = $property->getType();
                    if($types){
                        $convertBean->setAllowNull($types->allowsNull());
                    }
                    $class = $convertBean->className;
                    if(!$convertBean->isAllowNull()){
                        $this->{$property->name} = new $class($property->getDefaultValue());
                    }else{
                        $this->{$property->name} = null;
                    }
                    $this->properties[$property->name] = new $class();
                }else{
                    $this->{$property->name} = $property->getDefaultValue();
                    $this->properties[$property->name] = $property->getDefaultValue();
                }
            }
        }

        return array_keys($this->properties);
    }

    function toArray(int|callable $filter = null): array
    {
        $data = $this->jsonSerialize();
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

    final public function jsonSerialize(): array
    {
        $data = [];
        foreach ($this->properties as $key => $property){
            if($this->{$key} instanceof SplBean){
                $data[$key] = $this->{$key}->jsonSerialize();
            }else{
                $data[$key] = $this->{$key};
            }
        }
        return $data;
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
        foreach ($this->properties as $key => $val){
            if(key_exists($key,$data)){
                if($val instanceof SplBean){
                    $class = $val::class;
                    $val = $data[$key];
                    if(is_array($val)){
                        $class = new $class($val);
                        $this->{$key} = $class;
                    }else if(is_string($val)){
                        $arr = json_decode($val,true);
                        if(is_array($arr)){
                            $class = new $class($val);
                            $this->{$key} = $class;
                        }else{
                            throw new \Exception("data for property {$key} at class {$class} not a json format");
                        }
                    }elseif(is_object($val) && ($val::class == $class)){
                        $this->{$key} = $val;
                    }else{
                        throw new \Exception("data for property {$key} at class {$class} not a json format");
                    }
                }else{
                    $this->{$key} = $data[$key];
                }
            }
        }
        return $this;
    }
}
