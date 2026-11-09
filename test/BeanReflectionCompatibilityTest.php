<?php

declare(strict_types=1);

namespace EasySwoole\Spl\Test;

use EasySwoole\Spl\SplBean;
use EasySwoole\Spl\Attribute\ConvertBean;
use PHPUnit\Framework\TestCase;

final class BeanReflectionCompatibilityTest extends TestCase
{
    public function testProtectedOverridesPrivateNamesAndStaticFieldsKeepTheirShape(): void
    {
        $bean = new ReflectionChildBean(['shared' => 'restored', 'visible' => 'public', 'privateName' => 'child']);
        self::assertSame(['shared', 'privateName', 'visible'], $bean->allProperty());
        self::assertSame(['shared' => 'restored', 'privateName' => 'child', 'visible' => 'public'], $bean->toArray());
        self::assertNotContains('staticValue', $bean->allProperty());
    }

    public function testUninitializedAndNullablePropertiesKeepTheirOutput(): void
    {
        $bean = new ReflectionUninitializedBean();
        self::assertFalse((new \ReflectionProperty($bean, 'required'))->isInitialized($bean));
        self::assertTrue((new \ReflectionProperty($bean, 'nullable'))->isInitialized($bean));
        self::assertSame(['required' => null, 'nullable' => null], $bean->toArray());
        $bean->restore(['required' => 9]);
        self::assertSame(['required' => 9, 'nullable' => null], $bean->toArray());
        unset($bean->required);
        self::assertSame(['required' => null, 'nullable' => null], $bean->toArray());
    }

    public function testReadonlyPromotedPropertyIsNotReinitialized(): void
    {
        $bean = new ReflectionReadonlyPromotedBean(7);
        self::assertSame(['id' => 7], $bean->toArray());
        self::assertSame(['id' => 11], (new ReflectionReadonlyPromotedBean(11))->toArray());
    }

    public function testPromotedScalarPropertyKeepsItsConstructorValue(): void
    {
        self::assertSame(['name' => 'input'], (new ReflectionPromotedBean('input'))->toArray());
    }

    public function testNullablePromotionKeepsItsConstructorValue(): void
    {
        self::assertSame(['name' => 'input'], (new ReflectionNullablePromotedBean('input'))->toArray());
    }

    public function testReadonlyNullablePromotionKeepsItsConstructorValue(): void
    {
        self::assertSame(['name' => 'input'], (new ReflectionReadonlyNullablePromotedBean('input'))->toArray());
    }

    public function testDerivedAttributesAreFreshAndRestoreRunsBeforeInitialize(): void
    {
        ReflectionCountingAttribute::$instances = 0;
        $first = new ReflectionConvertedBean(['child' => ['value' => 'first']]);
        $second = new ReflectionConvertedBean(['child' => ['value' => 'second']]);
        self::assertSame(2, ReflectionCountingAttribute::$instances);
        self::assertSame('first', $first->valueSeenDuringInitialize);
        self::assertSame('second', $second->valueSeenDuringInitialize);
        $map = new \ReflectionProperty(SplBean::class, 'convertMap');
        self::assertNotSame($map->getValue($first)['child'], $map->getValue($second)['child']);
        self::assertSame(['child' => ['value' => 'first'], 'valueSeenDuringInitialize' => 'first'], $first->toArray());
        $first->restore(['child' => null]);
        self::assertNull($first->child);
        self::assertSame('second', $second->child->value);
    }

    public function testNestedDefaultObjectsRemainIndependent(): void
    {
        $first = new ReflectionDefaultConvertedBean();
        $first->child->value = 'modified';
        $second = new ReflectionDefaultConvertedBean();
        self::assertNotSame($first->child, $second->child);
        self::assertSame('default', $second->child->value);
    }

    public function testAttributeConstructorCanReenterConstructionWithoutSharingInstances(): void
    {
        ReflectionReenterAttribute::$instances = 0;
        ReflectionReenterAttribute::$nested = null;
        $outer = new ReflectionReenterBean();
        self::assertSame(2, ReflectionReenterAttribute::$instances);
        self::assertInstanceOf(ReflectionReenterBean::class, ReflectionReenterAttribute::$nested);
        self::assertSame(['child' => null], $outer->toArray());
        self::assertSame(['child' => null], ReflectionReenterAttribute::$nested->toArray());
    }

    public function testReflectionDescriptorsAreReusedAndRemainIsolatedByClass(): void
    {
        $first = new ReflectionConvertedBean(['child' => ['value' => 'first']]);
        $cache = new \ReflectionProperty(SplBean::class, 'propertyMetadata');
        $before = $cache->getValue();
        $second = new ReflectionConvertedBean(['child' => ['value' => 'second']]);
        $after = $cache->getValue();
        $firstChild = $before[ReflectionConvertedBean::class]['child'];
        $secondChild = $after[ReflectionConvertedBean::class]['child'];
        self::assertInstanceOf(\ReflectionProperty::class, $firstChild['property']);
        self::assertInstanceOf(\ReflectionAttribute::class, $firstChild['attributes'][0]);
        self::assertSame($firstChild['property'], $secondChild['property']);
        self::assertSame($firstChild['attributes'][0], $secondChild['attributes'][0]);
        self::assertNotSame($first->child, $second->child);

        $parent = new ReflectionParentBean();
        $child = new ReflectionChildBean();
        $metadata = $cache->getValue();
        self::assertSame(ReflectionParentBean::class,
            $metadata[ReflectionParentBean::class]['shared']['property']->getDeclaringClass()->getName());
        self::assertSame(ReflectionChildBean::class,
            $metadata[ReflectionChildBean::class]['shared']['property']->getDeclaringClass()->getName());
        self::assertSame('parent', $parent->toArray()['shared']);
        self::assertSame('child', $child->toArray()['shared']);
    }

    public function testPublicAndProtectedSignaturesRemainTheSame(): void
    {
        $expected = [
            '__construct' => ['public', '', [['data', '?array', true, null]]],
            'allProperty' => ['public', 'array', [['data', '?array', true, null]]],
            'toArray' => ['public', 'array', [['filter', 'callable|int|null', true, null]]],
            'getProperty' => ['public', 'mixed', [['name', 'string', false, null]]],
            'jsonSerialize' => ['public', 'array', []],
            '__toString' => ['public', 'string', []],
            'initialize' => ['protected', 'void', []],
            'restore' => ['public', 'static', [['data', 'array', true, []]]],
        ];
        $actual = [];
        foreach ((new \ReflectionClass(SplBean::class))->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED) as $method) {
            $parameters = [];
            foreach ($method->getParameters() as $parameter) {
                $parameters[] = [$parameter->getName(), (string)$parameter->getType(), $parameter->isDefaultValueAvailable(),
                    $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null];
            }
            $actual[$method->name] = [$method->isPublic() ? 'public' : 'protected', (string)$method->getReturnType(), $parameters];
        }
        self::assertSame($expected, $actual);
    }
}

class ReflectionParentBean extends SplBean
{
    protected string $shared = 'parent';
    private string $privateName = 'ignored';
    public string $visible = '';
    public static string $staticValue = 'ignored';
}

class ReflectionChildBean extends ReflectionParentBean
{
    protected string $shared = 'child';
    public string $privateName = '';
}

class ReflectionUninitializedBean extends SplBean
{
    public int $required;
    public ?string $nullable;
}

class ReflectionReadonlyPromotedBean extends SplBean
{
    public function __construct(public readonly int $id) { parent::__construct(); }
}

class ReflectionPromotedBean extends SplBean
{
    public function __construct(public string $name) { parent::__construct(); }
}

class ReflectionNullablePromotedBean extends SplBean
{
    public function __construct(public ?string $name) { parent::__construct(); }
}

class ReflectionReadonlyNullablePromotedBean extends SplBean
{
    public function __construct(public readonly ?string $name) { parent::__construct(); }
}

class ReflectionLeafBean extends SplBean
{
    public string $value = 'default';
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ReflectionCountingAttribute extends ConvertBean
{
    public static int $instances = 0;
    public function __construct(string $className) { self::$instances++; parent::__construct($className); }
}

class ReflectionConvertedBean extends SplBean
{
    #[ReflectionCountingAttribute(ReflectionLeafBean::class)]
    public ?ReflectionLeafBean $child;
    public ?string $valueSeenDuringInitialize = null;
    protected function initialize(): void { $this->valueSeenDuringInitialize = $this->child?->value; }
}

class ReflectionDefaultConvertedBean extends SplBean
{
    #[ConvertBean(ReflectionLeafBean::class, true)]
    public ReflectionLeafBean $child;
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ReflectionReenterAttribute extends ConvertBean
{
    public static int $instances = 0;
    public static ?ReflectionReenterBean $nested = null;
    public function __construct(string $className)
    {
        self::$instances++;
        if (self::$instances === 1) { self::$nested = new ReflectionReenterBean(); }
        parent::__construct($className);
    }
}

class ReflectionReenterBean extends SplBean
{
    #[ReflectionReenterAttribute(ReflectionLeafBean::class)]
    public ?ReflectionLeafBean $child;
}
