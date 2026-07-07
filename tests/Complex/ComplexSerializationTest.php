<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexSerializationTest extends TestCase
{
    /**
     * Test __serialize returns the expected associative array, excluding the computed
     * magnitude/phase properties.
     */
    public function testSerialize(): void
    {
        $z = new Complex(3, 4);
        $data = $z->__serialize();

        $this->assertSame([
            'real'      => 3.0,
            'imaginary' => 4.0,
        ], $data);
    }

    /**
     * Test jsonSerialize matches __serialize, and that json_encode() uses it automatically via
     * the JsonSerializable interface.
     */
    public function testJsonSerialize(): void
    {
        $z = new Complex(3, 4);

        $this->assertSame($z->__serialize(), $z->jsonSerialize());
        $this->assertSame('{"real":3,"imaginary":4}', json_encode($z));
    }

    /**
     * Test serialize()/unserialize() round-trips correctly.
     */
    public function testUnserializeRoundTrip(): void
    {
        $z = new Complex(3, 4);
        $restored = unserialize(serialize($z));

        $this->assertInstanceOf(Complex::class, $restored);
        $this->assertTrue($restored->equal($z));
    }

    /**
     * Test __unserialize throws if the data is missing the required keys.
     */
    public function testUnserializeMissingKeysThrows(): void
    {
        $z = new Complex();

        $this->expectException(DomainException::class);
        $z->__unserialize([
            'real' => 3.0,
        ]);
    }

    /**
     * Test __unserialize throws if the values are not numeric.
     */
    public function testUnserializeNonNumericThrows(): void
    {
        $z = new Complex();

        $this->expectException(DomainException::class);
        $z->__unserialize([
            'real'      => 'three',
            'imaginary' => 4.0,
        ]);
    }

    /**
     * Test __unserialize throws if the values are non-finite, since it reconstructs via the
     * constructor and so is subject to the same validation as normal construction. This guards
     * against a hand-crafted serialized string bypassing the constructor's finite-value check.
     */
    public function testUnserializeNonFiniteThrows(): void
    {
        $z = new Complex();

        $this->expectException(DomainException::class);
        $z->__unserialize([
            'real'      => NAN,
            'imaginary' => 1.0,
        ]);
    }
}
