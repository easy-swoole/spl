<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/22
 * Time: 下午2:54
 */

namespace EasySwoole\Spl;


class SplStream
{
    /** @var resource|null */
    private mixed $stream = null;
    private bool $seekable = false;
    private bool $readable = false;
    private bool $writable = false;
    public function __construct(mixed $resource = '', string $mode = 'r+')
    {
        self::assertValidMode($mode);
        if ($resource === false) {
            throw new \InvalidArgumentException('Invalid stream resource');
        }
        switch (gettype($resource)) {
            case 'resource': {
                $this->stream = $resource;
                break;
            }
            case 'object':{
                if(method_exists($resource, '__toString')) {
                    $resource = $resource->__toString();
                    $this->stream = $this->openMemoryStream($mode);
                    if ($resource !== '') {
                        fwrite($this->stream, $resource);
                    }
                    break;
                }else{
                    throw new \InvalidArgumentException('Invalid resource type: ' . gettype($resource));
                }
            }
            default:{
                $this->stream = $this->openMemoryStream($mode);
                try{
                    $resource = (string)$resource;
                    if ($resource !== '') {
                        fwrite($this->stream, $resource);
                    }
                }catch (\Throwable $exception){
                    throw new \InvalidArgumentException('Invalid resource type: ' . gettype($resource));
                }
            }
        }
        $info = stream_get_meta_data($this->stream);
        $this->seekable = $info['seekable'];
        $this->seek(0);
        $streamMode = $info['mode'];
        $this->readable = $streamMode[0] === 'r' || str_contains($streamMode, '+');
        $this->writable = in_array($streamMode[0], ['w', 'a', 'x', 'c'], true)
            || str_contains($streamMode, '+');
    }

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
        try {
            $this->seek(0);
            return (string) stream_get_contents($this->stream);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        // TODO: Implement close() method.
        $res = $this->detach();
        if(is_resource($res)){
            fclose($res);
        }
    }

    public function detach(): mixed
    {
        // TODO: Implement detach() method.
        if (!isset($this->stream)) {
            return null;
        }
        $this->readable = $this->writable = $this->seekable = false;
        $result = $this->stream;
        $this->stream = null;
        return $result;
    }

    public function getSize(): ?int
    {
        // TODO: Implement getSize() method.
        if (!is_resource($this->stream)) {
            return null;
        }
        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            return $stats['size'];
        }else{
            return null;
        }
    }

    public function tell(): int
    {
        // TODO: Implement tell() method.
        if (!is_resource($this->stream)) {
            throw new \RuntimeException('Cannot determine position of a detached stream');
        }
        $result = ftell($this->stream);
        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }
        return $result;
    }

    public function eof(): bool
    {
        // TODO: Implement eof() method.
        return !is_resource($this->stream) || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        // TODO: Implement isSeekable() method.
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        // TODO: Implement seek() method.
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        } elseif (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position '
                . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function rewind(): void
    {
        // TODO: Implement rewind() method.
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        // TODO: Implement isWritable() method.
        return $this->writable;
    }

    public function write(string $string): int
    {
        // TODO: Implement write() method.
        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }
        return $result;
    }

    public function isReadable(): bool
    {
        // TODO: Implement isReadable() method.
        return $this->readable;
    }

    public function read(int $length): string
    {
        // TODO: Implement read() method.
        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }
        if ($length < 0) {
            throw new \RuntimeException('Length parameter cannot be negative');
        }
        if (0 === $length) {
            return '';
        }
        $string = fread($this->stream, $length);
        if (false === $string) {
            throw new \RuntimeException('Unable to read from stream');
        }
        return $string;
    }

    public function getContents(): string
    {
        // TODO: Implement getContents() method.
        //注意与__toString的区别
        if (!$this->readable || !is_resource($this->stream)) {
            throw new \RuntimeException('Cannot read from a non-readable stream');
        }
        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }
        return $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        // TODO: Implement getMetadata() method.
        if (!isset($this->stream)) {
            return $key ? null : [];
        } elseif ($key === null) {
            return stream_get_meta_data($this->stream);
        } else {
            $meta = stream_get_meta_data($this->stream);
            return $meta[$key] ?? null;
        }
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->close();
    }

    /** @return resource|null */
    public function getStreamResource(): mixed
    {
        return $this->stream;
    }

    public function truncate(int $size = 0): bool
    {
        if (!is_resource($this->stream)) {
            throw new \RuntimeException('Cannot truncate a detached stream');
        }
        return ftruncate($this->stream, $size);
    }

    /** @return resource */
    private function openMemoryStream(string $mode): mixed
    {
        try {
            $stream = @fopen('php://memory', $mode);
        } catch (\ValueError $exception) {
            throw new \InvalidArgumentException("Invalid stream mode: {$mode}", 0, $exception);
        }
        if ($stream === false) {
            throw new \InvalidArgumentException("Invalid stream mode: {$mode}");
        }
        return $stream;
    }

    protected static function assertValidMode(string $mode): void
    {
        if (preg_match('/^[rwaxc](?:[bt]?\+|\+[bt]?|[bt])?$/', $mode) !== 1) {
            throw new \InvalidArgumentException("Invalid stream mode: {$mode}");
        }
    }
}
