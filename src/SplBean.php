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

    private static array|null $properties = null;
    private static array $convertMap = [];


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
        if(static::$properties == null){
            static::$properties = [];
            $class = new \ReflectionClass($this);
            $list = $class->getProperties(
                \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED
            );
            foreach ($list as $property) {
                if ($property->isStatic()) {
                    continue;
                }
                $this->{$property->name} = $property->getDefaultValue();
                static::$properties[$property->name] = $property->getDefaultValue();
                $convertBean = $property->getAttributes(ConvertBean::class);
                if($convertBean){
                    $convertBean = new ConvertBean(...$convertBean[0]->getArguments());
                    $types = $property->getType();
                    if($types){
                        $convertBean->setAllowNull($types->allowsNull());
                    }
                    static::$convertMap[$property->name] = $convertBean;
                }
            }
        }

        return array_keys(static::$properties);
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
        if (key_exists($name,static::$properties)) {
            return $this->$name;
        } else {
            return null;
        }
    }

    final public function jsonSerialize(): array
    {
        $data = [];
        foreach (static::$properties as $key => $property){
            $data[$key] = $this->{$key};
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
        foreach ($this->allProperty() as $key){
            if(key_exists($key,$data)){
                $this->{$key} = $data[$key];
            }
        }
        return $this;
    }


}
