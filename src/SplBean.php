<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/22
 * Time: 下午2:53
 */

namespace EasySwoole\Spl;


class SplBean implements \JsonSerializable
{
    const FILTER_NOT_NULL = 1;
    const FILTER_NOT_EMPTY = 2;
    const FILTER_NULL = 3;
    const FILTER_EMPTY = 4;


    public function __construct(array $data = null)
    {
        if ($data) {

        }
        $this->initialize();
    }

    final public function allProperty(): array
    {
        $data = [];
        $class = new \ReflectionClass($this);
        $protectedAndPublic = $class->getProperties(
            \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED
        );
        foreach ($protectedAndPublic as $item) {
            if ($item->isStatic()) {
                continue;
            }
            array_push($data, $item->getName());
        }
        $data = array_flip($data);
        unset($data['_keyMap']);
        unset($data['_classMap']);
        return array_flip($data);
    }

    function toArray(array $columns = null, $filter = null): array
    {
        $data = $this->jsonSerialize();
        if ($columns) {
            $data = array_intersect_key($data, array_flip($columns));
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
        if (isset($this->$name)) {
            return $this->$name;
        } else {
            return null;
        }
    }

    final public function jsonSerialize(): array
    {
        $data = [];

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

    }


}
