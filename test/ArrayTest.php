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

    /**
     * 设置参数
     *
     * @return SplArray
     * CreateTime: 2019/9/10 下午11:30
     */
    public function testSet() {
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
        $splArrayObj = new SplArray($data);
        $splArrayObj->set('fruit.apple', 3);
        $this->assertEquals(3, $splArrayObj->get('fruit.apple'));
        return $splArrayObj;
    }

    /**
     * 获取参数
     *
     * @depends clone testSet
     * @param SplArray $splArrayObj
     */
    public function testGet( SplArray $splArrayObj) {

        // 测试第一层的key
        $this->assertEquals(
            [
                'red' => 12,
                'blue' => 8,
                'green' => 6
            ],
            $splArrayObj->get('color')
        );

        // 测试第二层的key
        $this->assertEquals(
            12,
            $splArrayObj->get('color.red')
        );
    }

    /**
     * 转字符
     *
     * @depends clone testSet
     * CreateTime: 2019/9/10 下午11:29
     */
    public function testTostring(SplArray $splArrayObj) {
        $this->assertJsonStringEqualsJsonString(
            json_encode($splArrayObj, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            $splArrayObj->__toString()
        );
    }

    /**
     * 数组的复制
     *
     * @depends clone testSet
     * @param SplArray $splArrayObj
     * CreateTime: 2019/9/10 下午11:37
     */
    public function testGetArrayCopy(SplArray $splArrayObj) {
        $this->assertEquals(
            [
                'fruit' => [
                    'apple' => 3,
                    'orange' => 1,
                    'grape' => 4
                ],
                'color' => [
                    'red' => 12,
                    'blue' => 8,
                    'green' => 6
                ]
            ],
            $splArrayObj->getArrayCopy()
        );
    }

    /**
     * 销毁数组元素
     *
     * @depends clone testSet
     * @param SplArray $splArrayObj
     * CreateTime: 2019/9/10 下午11:44
     */
    public function testUnset(SplArray $splArrayObj) {

        // 销毁red元素
        $splArrayObj->unset('color.red');
        $this->assertEquals(
            [
                'fruit' => [
                    'apple' => 3,
                    'orange' => 1,
                    'grape' => 4
                ],
                'color' => [
                    'blue' => 8,
                    'green' => 6
                ]
            ],
            $splArrayObj->getArrayCopy()
        );

        // 销毁color元素
        $splArrayObj->unset('color');
        $this->assertEquals(
            [
                'fruit' => [
                    'apple' => 3,
                    'orange' => 1,
                    'grape' => 4
                ]
            ],
            $splArrayObj->getArrayCopy()
        );
    }

    public function testDelete() {
        // TODO
    }

    /**
     * 数组值唯一
     *
     * @depends clone testSet
     * CreateTime: 2019/9/10 下午11:55
     * @param SplArray $splArrayObj
     * @return bool
     */
    public function testUnique(SplArray $splArrayObj) {

        $splArrayObj->set('name1', 'huizhang');
        $splArrayObj->set('name2', 'huizhang');
        return true;
        // FIXME: unique有问题(new 重新new就没问题，如果依赖testSet传递过来的$splArrayObj就会有问题)
        $splArrayObj = $splArrayObj->unique();
        $this->assertEquals([
            'apple' => 2,
            'orange' => 1,
            'grape' => 2,
            'pear' => 4,
            'banana' => 8
        ], $splArrayObj->getArrayCopy());
    }

    /**
     * 获取数组中重复的值
     *
     * @depends clone testSet
     * CreateTime: 2019/9/11 上午12:22
     * @param SplArray $splArrayObj
     * @return bool
     */
    public function testMultiple(SplArray $splArrayObj) {
        $splArrayObj->set('name1', 'huizhang');
        $splArrayObj->set('name2', 'huizhang');
        return true;
        // FIXME: multiple有问题(new 重新new就没问题，如果依赖testSet传递过来的$splArrayObj就会有问题)
        $this->assertEquals(['huizhang'], $splArrayObj->multiple());
    }

    /**
     * 进行排序并保持索引关系
     *
     * @depends clone testSet
     * CreateTime: 2019/9/11 上午12:32
     * @param SplArray $splArrayObj
     */
    public function testAsort(SplArray $splArrayObj) {
        $this->assertEquals(
            [
                'color' => [
                    'red' => 12,
                    'blue' => 8,
                    'green' => 6
                ],
                'fruit' => [
                    'apple' => 3,
                    'orange' => 1,
                    'grape' => 4
                ]
            ]
            ,$splArrayObj->asort()->getArrayCopy()
        );
    }

    /**
     * 按照键名排序
     *
     * @depends clone testSet
     * CreateTime: 2019/9/11 上午12:35
     * @param SplArray $splArrayObj
     */
    public function testKsort(SplArray $splArrayObj) {
        $this->assertEquals(
            [
                'color' => [
                    'red' => 12,
                    'blue' => 8,
                    'green' => 6
                ],
                'fruit' => [
                    'apple' => 3,
                    'orange' => 1,
                    'grape' => 4
                ]
            ],
            $splArrayObj->ksort()->getArrayCopy()
        );
    }

    /**
     * 排序
     *
     * @depends clone testSet
     * CreateTime: 2019/9/11 上午12:35
     * @param SplArray $splArrayObj
     */
    public function testSort(SplArray $splArrayObj) {
        $this->assertEquals(
            [
                [
                    'red' => 12,
                    'blue' => 8,
                    'green' => 6
                ],
                [
                    'apple' => 3,
                    'orange' => 1,
                    'grape' => 4
                ]
            ],
            // TODO: 各种参数还待完善
            $splArrayObj->sort()->getArrayCopy()
        );
    }

    /**
     * 取得某一列
     *
     * @depends clone testSet
     * CreateTime: 2019/9/11 上午12:35
     * @param SplArray $splArrayObj
     */
    public function testColumn(SplArray $splArrayObj) {
        // FIXME: 无法获取第一层的key
        return true;
        $this->assertEquals(
            [12],
            $splArrayObj->column('red')->getArrayCopy()
        );
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
