<?php
/**
 * @CreateTime:   2019/9/14 下午10:24
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  SplFileStream 单元测试
 */
namespace EasySwoole\Spl\Test;

use EasySwoole\Spl\SplFileStream;
use PHPUnit\Framework\TestCase;

class FileStreamTest extends TestCase {

    protected function tearDown(): void
    {
        $file = getcwd() . '/test.txt';
        if (is_file($file)) {
            unlink($file);
        }
    }

    public function testConstruct() {
        $fileStream = new SplFileStream('./test.txt');
        $this->assertEquals('STDIO', $fileStream->getMetadata('stream_type'));
    }

    public function testLock() {
        $fileStream = new SplFileStream('./test.txt');
        $this->assertTrue($fileStream->lock());
    }

    public function testUnlock() {
        $fileStream = new SplFileStream('./test.txt');
        $this->assertTrue($fileStream->unlock());
    }

    public function testOpenFailureThrowsInsteadOfCreatingMemoryStream(): void
    {
        $this->expectException(\RuntimeException::class);
        new SplFileStream('/path/that/does/not/exist/file.txt', 'r');
    }

    public function testInvalidModeThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SplFileStream('./test.txt', 'invalid');
    }
}
