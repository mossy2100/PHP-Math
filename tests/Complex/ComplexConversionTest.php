<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Complex::class)]
class ComplexConversionTest extends TestCase
{
    /**
     * Test __toString for real numbers.
     */
    public function testToStringReal(): void
    {
        $z = new Complex(5, 0);
        $this->assertSame('5', (string)$z);

        $z2 = new Complex(-3.14, 0);
        $this->assertSame('-3.14', (string)$z2);

        $z3 = new Complex(0, 0);
        $this->assertSame('0', (string)$z3);
    }

    /**
     * Test __toString for pure imaginary numbers.
     */
    public function testToStringPureImaginary(): void
    {
        $z1 = new Complex(0, 1);
        $this->assertSame('i', (string)$z1);

        $z2 = new Complex(0, -1);
        $this->assertSame('-i', (string)$z2);

        $z3 = new Complex(0, 5);
        $this->assertSame('5i', (string)$z3);

        $z4 = new Complex(0, -3.5);
        $this->assertSame('-3.5i', (string)$z4);
    }

    /**
     * Test __toString for complex numbers.
     */
    public function testToStringComplex(): void
    {
        $z1 = new Complex(3, 4);
        $this->assertSame('3 + 4i', (string)$z1);

        $z2 = new Complex(3, -4);
        $this->assertSame('3 - 4i', (string)$z2);

        $z3 = new Complex(-3, 4);
        $this->assertSame('-3 + 4i', (string)$z3);

        $z4 = new Complex(-3, -4);
        $this->assertSame('-3 - 4i', (string)$z4);

        // Test with coefficient of 1
        $z5 = new Complex(5, 1);
        $this->assertSame('5 + i', (string)$z5);

        $z6 = new Complex(5, -1);
        $this->assertSame('5 - i', (string)$z6);
    }

    /**
     * Test that __toString() suppresses floating-point representation noise from arithmetic
     * by using Floats::format()'s default precision (7 significant digits via 'g' specifier),
     * rather than PHP's raw float-to-string which uses serialize_precision (17 digits).
     */
    public function testToStringSuppressesFloatingPointNoise(): void
    {
        // 0.1 + 0.2 is the canonical IEEE-754 surprise: PHP's (string) cast renders it as
        // '0.30000000000000004' because serialize_precision is 17.
        $z = new Complex(0.1 + 0.2, 0);
        $this->assertSame('0.3', (string)$z);

        // Same noise on the imaginary part.
        $z = new Complex(0, 0.1 + 0.2);
        $this->assertSame('0.3i', (string)$z);

        // Both parts noisy at once.
        $z = new Complex(0.1 + 0.2, 0.1 + 0.2);
        $this->assertSame('0.3 + 0.3i', (string)$z);

        // A subtraction that produces a tiny non-zero residue. Without rounding, the imaginary
        // part would render as something like '4.4408920985006e-16'. With Floats::format()'s
        // default 'g' precision of 7, it gets formatted in scientific notation but cleanly.
        $z = new Complex(1.0, 0.1 + 0.2 - 0.3);
        // The imaginary part is ~5.55e-17 — still non-zero, so isReal() returns false.
        $this->assertStringStartsWith('1 + ', (string)$z);
        $this->assertStringEndsWith('i', (string)$z);
    }

    /**
     * Test toArray.
     */
    public function testToArray(): void
    {
        $z = new Complex(3, 4);
        $array = $z->toArray();

        $this->assertIsArray($array); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertCount(2, $array);
        $this->assertSame(3.0, $array[0]);
        $this->assertSame(4.0, $array[1]);
    }

    /**
     * Test toObject.
     */
    public function testToObject(): void
    {
        $z = new Complex(3, 4);
        $obj = $z->toObject();

        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertSame(3.0, $obj->real);
        $this->assertSame(4.0, $obj->imaginary);
    }
}
