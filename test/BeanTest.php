<?php

declare(strict_types=1);

namespace EasySwoole\Spl\Test;

use EasySwoole\Spl\AbstractInterface\ConvertBeanInterface;
use EasySwoole\Spl\Attribute\ConvertBean;
use EasySwoole\Spl\SplBean;
use PHPUnit\Framework\TestCase;

final class BeanTest extends TestCase
{
    public function testPropertiesAreRestoredAndUnknownInputIsIgnored(): void
    {
        $bean = new BasicBean([
            'name' => 'EasySwoole',
            'count' => 0,
            'nullable' => null,
            'unknown' => 'ignored',
        ]);

        self::assertSame(['name', 'count', 'nullable', 'initialized'], $bean->allProperty());
        self::assertSame([
            'name' => 'EasySwoole',
            'count' => 0,
            'nullable' => null,
            'initialized' => true,
        ], $bean->toArray());
        self::assertNull($bean->getProperty('unknown'));
    }

    public function testBuiltInFiltersHaveExplicitEmptyValueSemantics(): void
    {
        $bean = new FilterBean();

        self::assertSame([
            'zero' => 0,
            'zeroString' => '0',
            'false' => false,
            'emptyString' => '',
            'emptyArray' => [],
            'text' => 'value',
        ], $bean->toArray(SplBean::FILTER_NOT_NULL));
        self::assertSame(['null' => null], $bean->toArray(SplBean::FILTER_NULL));
        self::assertSame(['text' => 'value'], $bean->toArray(SplBean::FILTER_NOT_EMPTY));
        self::assertSame([
            'zero' => 0,
            'zeroString' => '0',
            'false' => false,
            'emptyString' => '',
            'emptyArray' => [],
            'null' => null,
        ], $bean->toArray(SplBean::FILTER_EMPTY));
    }

    public function testCallableFilterReceivesValuesAndPreservesKeys(): void
    {
        $bean = new FilterBean();

        self::assertSame(
            ['zero' => 0, 'zeroString' => '0'],
            $bean->toArray(static fn (mixed $value): bool => $value === 0 || $value === '0')
        );
    }

    public function testUnknownIntegerFilterLeavesDataUnfiltered(): void
    {
        $bean = new FilterBean();

        self::assertSame($bean->toArray(), $bean->toArray(999));
    }

    public function testSplBeanConverterAcceptsArrayJsonAndExistingInstance(): void
    {
        $fromArray = new RequiredAddressBean(['address' => ['city' => 'Xiamen']]);
        $fromJson = new RequiredAddressBean(['address' => '{"city":"Shanghai"}']);
        $address = new AddressBean(['city' => 'Beijing']);
        $fromObject = new RequiredAddressBean(['address' => $address]);

        self::assertInstanceOf(AddressBean::class, $fromArray->address);
        self::assertSame('Xiamen', $fromArray->address->city);
        self::assertSame('Shanghai', $fromJson->address->city);
        self::assertSame($address, $fromObject->address);
        self::assertSame(['address' => ['city' => 'Xiamen']], $fromArray->toArray());
    }

    public function testNullableSplBeanConverterCanBeClearedByRestore(): void
    {
        $bean = new NullableAddressBean(['address' => ['city' => 'Xiamen']]);
        $bean->restore(['address' => null]);

        self::assertNull($bean->address);
        self::assertSame(['address' => null], $bean->toArray());
    }

    public function testNullableSplBeanConverterStillRejectsInvalidNonNullValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NullableAddressBean(['address' => false]);
    }

    public function testRequiredSplBeanConverterRejectsMissingAndNullValues(): void
    {
        try {
            new RequiredAddressBean();
            self::fail('A missing required converted property must fail.');
        } catch (\Exception $exception) {
            self::assertStringContainsString('cannot be null', $exception->getMessage());
        }

        $bean = new RequiredAddressBean(['address' => ['city' => 'Xiamen']]);
        $this->expectException(\InvalidArgumentException::class);
        $bean->restore(['address' => null]);
    }

    public function testCreateObjectWhenNullCreatesBothConverterKinds(): void
    {
        $bean = new DefaultsBean();

        self::assertInstanceOf(AddressBean::class, $bean->address);
        self::assertInstanceOf(JsonValue::class, $bean->metadata);
        self::assertSame([], $bean->metadata->value);
    }

    public function testInterfaceConverterMatchesFastDbRoundTripContract(): void
    {
        $bean = new MetadataBean(['metadata' => '{"role":"admin"}']);

        self::assertInstanceOf(JsonValue::class, $bean->metadata);
        self::assertSame(['role' => 'admin'], $bean->metadata->value);
        self::assertSame(['metadata' => '{"role":"admin"}'], $bean->toArray());

        $sameObject = new JsonValue(['role' => 'editor']);
        $bean->restore(['metadata' => $sameObject]);
        self::assertSame($sameObject, $bean->metadata);

        $bean->restore(['metadata' => null]);
        self::assertNull($bean->metadata);
    }

    public function testOrdinaryObjectsWithToValueAreNotImplicitlyConverted(): void
    {
        $value = new UnannotatedValue();
        $bean = new OrdinaryObjectBean(['value' => $value]);

        self::assertSame($value, $bean->toArray()['value']);
    }

    public function testInvalidJsonAndWrongObjectTypeAreRejected(): void
    {
        foreach (['not-json', new \stdClass()] as $invalid) {
            try {
                new NullableAddressBean(['address' => $invalid]);
                self::fail('Invalid converted input must fail.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testJsonSerializationUsesNestedBeanValues(): void
    {
        $bean = new RequiredAddressBean(['address' => ['city' => 'Xiamen']]);

        self::assertSame('{"address":{"city":"Xiamen"}}', json_encode($bean, JSON_THROW_ON_ERROR));
        self::assertSame('{"address":{"city":"Xiamen"}}', (string) $bean);
    }
}

final class BasicBean extends SplBean
{
    public string $name = '';
    protected int $count = 1;
    public ?string $nullable = null;
    private string $privateValue = 'hidden';
    public bool $initialized = false;

    protected function initialize(): void
    {
        $this->initialized = true;
    }
}

final class FilterBean extends SplBean
{
    public int $zero = 0;
    public string $zeroString = '0';
    public bool $false = false;
    public string $emptyString = '';
    public array $emptyArray = [];
    public ?string $null = null;
    public string $text = 'value';
}

final class AddressBean extends SplBean
{
    public string $city = '';
}

final class RequiredAddressBean extends SplBean
{
    #[ConvertBean(AddressBean::class)]
    public AddressBean $address;
}

final class NullableAddressBean extends SplBean
{
    #[ConvertBean(AddressBean::class)]
    public ?AddressBean $address;
}

final class DefaultsBean extends SplBean
{
    #[ConvertBean(AddressBean::class, createObjectWhenNull: true)]
    public AddressBean $address;

    #[ConvertBean(JsonValue::class, createObjectWhenNull: true)]
    public JsonValue $metadata;
}

final class MetadataBean extends SplBean
{
    #[ConvertBean(JsonValue::class)]
    public ?JsonValue $metadata;
}

final class JsonValue implements ConvertBeanInterface
{
    public function __construct(public array $value)
    {
    }

    public static function toObject(mixed $data): object
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new self(is_array($data) ? $data : []);
    }

    public function toValue(): string
    {
        return json_encode($this->value, JSON_THROW_ON_ERROR);
    }
}

final class OrdinaryObjectBean extends SplBean
{
    public object $value;
}

final class UnannotatedValue
{
    public function toValue(): string
    {
        return 'must not be called';
    }
}
