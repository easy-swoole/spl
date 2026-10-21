<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/22
 * Time: 下午2:55
 */

namespace EasySwoole\Spl;


class SplFileStream extends SplStream
{
    public function __construct(string $file, string $mode = 'c+')
    {
        self::assertValidMode($mode);
        try {
            $fp = @fopen($file, $mode);
        } catch (\ValueError $exception) {
            throw new \InvalidArgumentException("Invalid file mode: {$mode}", 0, $exception);
        }
        if ($fp === false) {
            throw new \RuntimeException("Unable to open file: {$file}");
        }
        parent::__construct($fp);
    }

    public function lock(int $mode = LOCK_EX): bool
    {
        return flock($this->getStreamResource(), $mode);
    }

    public function unlock(int $mode = LOCK_UN): bool
    {
        return flock($this->getStreamResource(), $mode);
    }
}
