<?php

declare(strict_types=1);

namespace EasySwoole\Spl\Test;

use EasySwoole\Spl\SplDoubleLink;
use PHPUnit\Framework\TestCase;

final class DoubleLinkTest extends TestCase
{
    public function testLazilyCreatesAndCachesAdjacentNodes(): void
    {
        $link = new NamedLink('root');

        $next = $link->next('next');
        $previous = $link->pre('previous');

        self::assertInstanceOf(NamedLink::class, $next);
        self::assertInstanceOf(NamedLink::class, $previous);
        self::assertSame('next', $next->name);
        self::assertSame('previous', $previous->name);
        self::assertSame($next, $link->next('ignored'));
        self::assertSame($previous, $link->pre('ignored'));
        self::assertTrue($link->hasNext());
        self::assertTrue($link->hasPre());
        self::assertTrue($link->hashNext());
        self::assertTrue($link->hashPre());
    }

    public function testAdjacentNodesCanBeDeletedAndRecreated(): void
    {
        $link = new NamedLink('root');
        $first = $link->next('first');

        self::assertSame($link, $link->delNext());
        self::assertFalse($link->hasNext());
        self::assertNotSame($first, $link->next('second'));
        self::assertSame('second', $link->next()->name);

        $link->pre('first')->delPre();
        self::assertTrue($link->hasPre());
        self::assertSame($link, $link->delPre());
        self::assertFalse($link->hasPre());
    }
}

final class NamedLink extends SplDoubleLink
{
    public function __construct(public string $name = '')
    {
    }
}
