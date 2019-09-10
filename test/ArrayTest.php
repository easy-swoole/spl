<?php
/**
 * @CreateTime:   2019/9/9 下午11:28
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  SplArray 单元测试
 */
namespace EasySwoole\Spl\Test;

use PHPUnit\Framework\TestCase;

use EasySwoole\Spl\SplArray;

class ArrayTest extends TestCase {

    public function testGet() {

        $data = [
            'fruit' => [
                'apple' => 2,
                'orange' => 1,
                'grape' => 4
            ],
            'color' => [
                'red' => 12,
                'blue' => 8,
                'green' => 6
            ]
        ];
        $splArray = new SplArray($data);

        // 测试第一层的key
        $this->assertEquals(
            [
            'red' => 12,
            'blue' => 8,
            'green' => 6
            ],
            $splArray->get('color')
        );

        // 测试第二层的key
        $this->assertEquals(
            12,
            $splArray->get('color.red')
        );
    }

    public function testSet() {
        // todo
    }

    public function testTostring() {
        // todo
    }

    public function testGetArrayCopy() {
        // todo
    }

    public function testUnset() {
        // todo
    }

    public function testUnique() {
        // todo
    }

    public function testMultiple() {
        // todo
    }

    public function testAsort() {
        // todo
    }

    public function testKsort() {
        // todo
    }

    public function testSort() {
        // todo
    }

    public function testColumn() {
        // todo
    }

    public function testFlip() {
        // todo
    }

    public function testFilter() {
        // todo
    }

    public function testKeys() {
        // todo
    }

    public function testValues() {
        // todo
    }

    public function testFlush() {
        // todo
    }

    public function testLoadArray() {
        // todo
    }

    public function testToXML() {
        // todo
    }
}
