<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DivisionByZeroError;
use DomainException;
use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalSerializationTest extends TestCase
{
    /**
     * Test jsonSerialize returns the expected associative array.
     */
    public function testJsonSerialize(): void
    {
        $r = new Rational(3, 4);

        $this->assertSame([
            'numerator'   => 3,
            'denominator' => 4,
        ], $r->jsonSerialize());
        $this->assertSame('{"numerator":3,"denominator":4}', json_encode($r));
    }

    /**
     * Test serialize()/unserialize() round-trips correctly. There is no custom __serialize(), so
     * this also confirms PHP's default serialization of a private(set) property produces a plain,
     * unmangled key that __unserialize() can read.
     */
    public function testUnserializeRoundTrip(): void
    {
        $r = new Rational(3, 4);
        $restored = unserialize(serialize($r));

        $this->assertInstanceOf(Rational::class, $restored);
        $this->assertTrue($restored->equal($r));
    }

    /**
     * Test __unserialize reconstructs via the constructor, so an un-reduced numerator/denominator
     * pair (e.g. from a hand-crafted string) still gets canonicalized to lowest terms.
     */
    public function testUnserializeReducesToLowestTerms(): void
    {
        $r = new Rational();

        $r->__unserialize([
            'numerator'   => 12,
            'denominator' => 16,
        ]);

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    /**
     * Test __unserialize throws if the data is missing the required keys.
     */
    public function testUnserializeMissingKeysThrows(): void
    {
        $r = new Rational();

        $this->expectException(DomainException::class);
        $r->__unserialize([
            'numerator' => 3,
        ]);
    }

    /**
     * Test __unserialize throws if the values are not integers.
     */
    public function testUnserializeNonIntegerThrows(): void
    {
        $r = new Rational();

        $this->expectException(DomainException::class);
        $r->__unserialize([
            'numerator'   => 'three',
            'denominator' => 4,
        ]);
    }

    /**
     * Test __unserialize throws if given a float rather than an int, since Rational's canonical
     * form requires integer numerator/denominator (unlike Complex, which accepts floats).
     */
    public function testUnserializeFloatThrows(): void
    {
        $r = new Rational();

        $this->expectException(DomainException::class);
        $r->__unserialize([
            'numerator'   => 3.5,
            'denominator' => 4,
        ]);
    }

    /**
     * Test __unserialize throws if the denominator is zero, since it reconstructs via the
     * constructor and so is subject to the same validation as normal construction. This guards
     * against a hand-crafted serialized string bypassing that check.
     */
    public function testUnserializeZeroDenominatorThrows(): void
    {
        $r = new Rational();

        $this->expectException(DivisionByZeroError::class);
        $r->__unserialize([
            'numerator'   => 1,
            'denominator' => 0,
        ]);
    }
}
