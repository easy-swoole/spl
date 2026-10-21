<?php


namespace EasySwoole\Spl;


class SplDoubleLink
{
    private ?SplDoubleLink $next = null;
    private ?SplDoubleLink $pre = null;

    public function hashNext(): bool
    {
        return $this->hasNext();
    }

    public function hashPre(): bool
    {
        return $this->hasPre();
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    public function hasPre(): bool
    {
        return $this->pre !== null;
    }

    public function next(mixed ...$arg): SplDoubleLink
    {
        if(!$this->next){
            $this->next = $this->newInstance(...$arg);
        }
        return $this->next;
    }

    public function pre(mixed ...$arg): SplDoubleLink
    {
        if(!$this->pre){
            $this->pre = $this->newInstance(...$arg);
        }
        return $this->pre;
    }

    public function delPre(): SplDoubleLink
    {
        $this->pre = null;
        return $this;
    }

    public function delNext(): SplDoubleLink
    {
        $this->next = null;
        return $this;
    }

    private function newInstance(mixed ...$arg): SplDoubleLink
    {
        $ref = new \ReflectionClass(static::class);
        return $ref->newInstanceArgs($arg);
    }

}
