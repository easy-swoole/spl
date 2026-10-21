<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/22
 * Time: 下午2:52
 */

namespace EasySwoole\Spl;


class SplArray extends \ArrayObject
{
    public function __get(string $name): mixed
    {
        if (isset($this[$name])) {
            return $this[$name];
        } else {
            return null;
        }
    }

    public function __set(string $name, mixed $value): void
    {
        $this[$name] = $value;
    }

    public function __toString(): string
    {
        return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function getArrayCopy(): array
    {
        return (array)$this;
    }

    public function set(string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $temp = $this;
        foreach ($segments as $key) {
            $temp = &$temp[$key];
        }
        $temp = $value;
    }

    public function unset(string $path): void
    {
        $segments = explode('.', $path);
        $temp = $this;
        while (count($segments) > 1) {
            $key = array_shift($segments);
            if (!is_array($temp) && !$temp instanceof \ArrayAccess) {
                return;
            }
            if (!isset($temp[$key])) {
                return;
            }
            $temp = &$temp[$key];
        }
        $finalKey = array_shift($segments);
        if ((is_array($temp) || $temp instanceof \ArrayAccess) && isset($temp[$finalKey])) {
            unset($temp[$finalKey]);
        }
    }

    public function get(string|int|array|null $key = null, mixed $default = null, mixed $target = null): mixed
    {
        if ($target === null) {
            $target = $this->getArrayCopy();
        }
        if (is_null($key)) {
            return $target;
        }
        $key = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);
        while (!is_null($segment = array_shift($key))) {
            if ((is_array($target) || $target instanceof \ArrayAccess) && isset($target[$segment])) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                if ($segment === '*') {
                    $data = [];
                    if (!is_iterable($target)) {
                        return $default;
                    }
                    foreach ($target as $item) {
                        $data[] = self::get($key, $default,$item);
                    }
                    return $data;
                } else {
                    return $default;
                }
            }
        }
        return $target;
    }

    public function delete(string $key): void
    {
        $this->unset($key);
    }

    /**
     * 数组去重取唯一的值
     * @return SplArray
     */
    public function unique(): SplArray
    {
        return new SplArray(array_unique($this->getArrayCopy(), SORT_REGULAR));
    }

    /**
     * 获取数组中重复的值
     * @return SplArray
     */
    public function multiple(): SplArray
    {
        $unique_arr = array_unique($this->getArrayCopy(), SORT_REGULAR);
        return new SplArray(array_udiff_uassoc($this->getArrayCopy(), $unique_arr, function ($key1, $key2) {
            if ($key1 === $key2) {
                return 0;
            }
            return 1;
        }, function ($value1, $value2) {
            if ($value1 === $value2) {
                return 0;
            }
            return 1;
        }));
    }


    #[\ReturnTypeWillChange]
    public function asort(int $flags = SORT_REGULAR): bool
    {
        return parent::asort($flags);
    }

    #[\ReturnTypeWillChange]
    public function ksort(int $flags = SORT_REGULAR): bool
    {
        return parent::ksort($flags);
    }

    /**
     * 自定义排序
     * @param int $sort_flags
     * @return SplArray
     */
    public function sort(int $sort_flags = SORT_REGULAR): SplArray
    {
        $temp = $this->getArrayCopy();
        sort($temp, $sort_flags);
        return new SplArray($temp);
    }

    /**
     * 取得某一列
     * @param string      $column
     * @param null|string $index_key
     * @return SplArray
     */
    public function column(int|string|null $column, int|string|null $index_key = null): SplArray
    {
        return new SplArray(array_column($this->getArrayCopy(), $column, $index_key));
    }

    /**
     * 交换数组中的键和值
     * @return SplArray
     */
    public function flip(): SplArray
    {
        return new SplArray(array_flip($this->getArrayCopy()));
    }

    /**
     * 过滤本数组
     * @param string|array $keys 需要取得/排除的键
     * @param bool         $exclude true则排除设置的键名 false则仅获取设置的键名
     * @return SplArray
     */
    public function filter(string|array $keys, bool $exclude = false): SplArray
    {
        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }
        $new = array();
        foreach ($this->getArrayCopy() as $name => $value) {
            if (!$exclude) {
                in_array($name, $keys) ? $new[$name] = $value : null;
            } else {
                in_array($name, $keys) ? null : $new[$name] = $value;
            }
        }
        return new SplArray($new);
    }


    public function keys(string|int|null $path = null): array
    {
        if ($path !== null && $path !== '') {
            $temp = $this->get($path);
            if (is_array($temp)) {
                return array_keys($temp);
            } else {
                return [];
            }
        }
        return array_keys((array)$this);
    }

    /**
     * 提取数组中的值
     * @return SplArray
     */
    public function values(): SplArray
    {
        return new SplArray(array_values($this->getArrayCopy()));
    }

    public function flush(): SplArray
    {
        foreach ($this->getArrayCopy() as $key => $item) {
            unset($this[$key]);
        }
        return $this;
    }

    public function loadArray(array $data): SplArray
    {
        parent::__construct($data);
        return $this;
    }

    public function merge(array $data): SplArray
    {
        return $this->loadArray($data + $this->getArrayCopy());
    }

    /*
     $test = new \EasySwoole\Spl\SplArray([
        'title'=>'title',
        'items'=>[
            ['title'=>'Some string', 'number' => 1],
            ['title'=>'Some string', 'number' => 2],
            ['title'=>'Some string', 'number' => 3]
        ]
    ]);
     */
    public function toXML(bool $CD_DATA = false, string $rootName = 'xml', string $subArrayItemKey = 'item'): string
    {
        $data = $this->getArrayCopy();
        if ($CD_DATA) {
            /*
             * 默认制定
             */
            $xml = new class("<{$rootName}></{$rootName}>") extends \SimpleXMLElement
            {
                public function addCData(string $cdata_text): void
                {
                    $dom = dom_import_simplexml($this);
                    $cdata = $dom->ownerDocument->createCDATASection((string)$cdata_text);
                    $dom->appendChild($cdata);
                }
            };
        } else {
            $xml = new \SimpleXMLElement( "<{$rootName} ></{$rootName}>");
        }
        $parser = function (\SimpleXMLElement $xml, array $data) use (&$parser, $CD_DATA, $subArrayItemKey): void {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    if (!is_numeric($k)) {
                        $ch = $xml->addChild($k);
                    } else {
                        $ch = $xml->addChild($subArrayItemKey);
                    }
                    $parser($ch, $v);
                } else {
                    if (is_numeric($k)) {
                        $xml->addChild($subArrayItemKey, (string)$v);
                    } else {
                        if ($CD_DATA) {
                            $n = $xml->addChild($k);
                            $n->addCData((string)$v);
                        } else {
                            $xml->addChild($k, (string)$v);
                        }
                    }
                }
            }
        };
        $parser($xml, $data);
        unset($parser);
        $str = $xml->asXML();
        if ($str === false) {
            throw new \RuntimeException('Unable to serialize XML');
        }
        return substr($str, strpos($str, "\n") + 1);
    }

}
