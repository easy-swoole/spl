<?php

declare(strict_types=1);

namespace EasySwoole\Spl\Test;

use EasySwoole\Spl\Attribute\ConvertBean;
use EasySwoole\Spl\SplBean;
use PHPUnit\Framework\TestCase;

final class BeanInitializationTest extends TestCase
{
    public function testPromotedNullableValuesArePreservedBeforeInitialize(): void
    {
        $bean = new InitializationPromotedBean('constructor value');
        self::assertSame('constructor value', $bean->name);
        self::assertSame('constructor value', $bean->nameSeenDuringInitialize());
        self::assertSame(['name' => 'constructor value'], $bean->toArray());
        self::assertSame('constructor default', (new InitializationPromotedBean())->name);
        self::assertNull((new InitializationPromotedBean(null))->name);
    }

    public function testExplicitDataCanStillRestoreMutablePromotedValues(): void
    {
        $bean = new InitializationPromotedBean('constructor value', ['name' => 'restored value']);
        self::assertSame('restored value', $bean->name);
        self::assertSame('restored value', $bean->nameSeenDuringInitialize());
        $bean->restore(['name' => null]);
        self::assertNull($bean->name);
    }

    public function testProtectedPromotedNullableValuesArePreserved(): void
    {
        $bean = new InitializationProtectedPromotedBean('protected value');
        self::assertSame(['name' => 'protected value'], $bean->toArray());
    }

    public function testPromotedConvertedValuesKeepTheirExistingInstances(): void
    {
        $address = new InitializationAddressBean(['city' => 'Xiamen']);
        $nullable = new InitializationNullableConvertedBean($address);
        $required = new InitializationRequiredConvertedBean($address);
        self::assertSame($address, $nullable->address);
        self::assertSame($address, $required->address);
        self::assertSame(['address' => ['city' => 'Xiamen']], $required->toArray());

        // Preserving the promoted instance must still register its conversion metadata.
        $nullable->restore(['address' => ['city' => 'Shanghai']]);
        self::assertInstanceOf(InitializationAddressBean::class, $nullable->address);
        self::assertSame('Shanghai', $nullable->address->city);
        $nullable->restore(['address' => null]);
        self::assertNull($nullable->address);
    }

    public function testReadOnlyPromotedNullableValuesAreNotReinitialized(): void
    {
        $bean = new InitializationReadOnlyPromotedBean('readonly value');
        self::assertSame('readonly value', $bean->name);
        self::assertSame(['name' => 'readonly value'], $bean->toArray());
        self::assertNull((new InitializationReadOnlyPromotedBean(null))->name);
    }

    public function testReadOnlyPromotedConvertedValuesAreNotReinitialized(): void
    {
        $address = new InitializationAddressBean(['city' => 'Xiamen']);
        $bean = new InitializationReadOnlyConvertedBean($address);
        self::assertSame($address, $bean->address);
        self::assertSame(['address' => ['city' => 'Xiamen']], $bean->toArray());
    }

    public function testExplicitDataDoesNotSilentlyRewriteReadOnlyPromotedValues(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('readonly property');
        new InitializationReadOnlyPromotedBean('constructor value', ['name' => 'replacement']);
    }

    public function testOrdinaryFieldsStillUseTheirExistingDefaultInitialization(): void
    {
        $bean = new InitializationOrdinaryBean();
        self::assertNull($bean->nullable);
        self::assertSame('declared default', $bean->withDefault);
    }

    public function testUninitializedPromotedNullableFieldStillGetsInitialized(): void
    {
        $bean = new InitializationUnsetPromotedBean('discarded');
        self::assertNull($bean->name);
        self::assertSame(['name' => null], $bean->toArray());
    }
}

class InitializationPromotedBean extends SplBean
{
    private ?string $nameSeen = null;

    public function __construct(public ?string $name = 'constructor default', ?array $data = null)
    {
        parent::__construct($data);
    }

    protected function initialize(): void { $this->nameSeen = $this->name; }
    public function nameSeenDuringInitialize(): ?string { return $this->nameSeen; }
}

class InitializationProtectedPromotedBean extends SplBean
{
    public function __construct(protected ?string $name)
    {
        parent::__construct();
    }
}

class InitializationAddressBean extends SplBean
{
    public string $city = '';
}

class InitializationNullableConvertedBean extends SplBean
{
    public function __construct(#[ConvertBean(InitializationAddressBean::class)] public ?InitializationAddressBean $address)
    {
        parent::__construct();
    }
}

class InitializationRequiredConvertedBean extends SplBean
{
    public function __construct(#[ConvertBean(InitializationAddressBean::class)] public InitializationAddressBean $address)
    {
        parent::__construct();
    }
}

class InitializationReadOnlyPromotedBean extends SplBean
{
    public function __construct(public readonly ?string $name, ?array $data = null)
    {
        parent::__construct($data);
    }
}

class InitializationReadOnlyConvertedBean extends SplBean
{
    public function __construct(#[ConvertBean(InitializationAddressBean::class)] public readonly ?InitializationAddressBean $address)
    {
        parent::__construct();
    }
}

class InitializationOrdinaryBean extends SplBean
{
    public ?string $nullable;
    public string $withDefault = 'declared default';

    public function __construct()
    {
        $this->nullable = 'assigned before parent';
        $this->withDefault = 'assigned before parent';
        parent::__construct();
    }
}

class InitializationUnsetPromotedBean extends SplBean
{
    public function __construct(public ?string $name)
    {
        unset($this->name);
        parent::__construct();
    }
}
