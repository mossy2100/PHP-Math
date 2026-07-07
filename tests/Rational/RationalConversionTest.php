<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalConversionTest extends TestCase
{
    /**
     * Test toFloat conversion.
     */
    public function testToFloat(): void
    {
        $r = new Rational(1, 2);
        $this->assertSame(0.5, $r->toFloat());

        $r2 = new Rational(3, 4);
        $this->assertSame(0.75, $r2->toFloat());

        $r3 = new Rational(5, 1);
        $this->assertSame(5.0, $r3->toFloat());

        $r4 = new Rational(-7, 2);
        $this->assertSame(-3.5, $r4->toFloat());
    }

    /**
     * Test __toString for whole numbers.
     */
    public function testToStringWholeNumber(): void
    {
        $r = new Rational(5, 1);
        $this->assertSame('5', (string)$r);

        $r2 = new Rational(10, 2);
        $this->assertSame('5', (string)$r2);

        $r3 = new Rational(0);
        $this->assertSame('0', (string)$r3);

        $r4 = new Rational(-7, 1);
        $this->assertSame('-7', (string)$r4);
    }

    /**
     * Test __toString for fractions.
     */
    public function testToStringFraction(): void
    {
        $r = new Rational(3, 4);
        $this->assertSame('3/4', (string)$r);

        $r2 = new Rational(-5, 6);
        $this->assertSame('-5/6', (string)$r2);

        $r3 = new Rational(1, 2);
        $this->assertSame('1/2', (string)$r3);
    }

    /**
     * Test __toString with reduced fractions.
     */
    public function testToStringReduced(): void
    {
        $r = new Rational(6, 8);
        $this->assertSame('3/4', (string)$r);

        $r2 = new Rational(10, 15);
        $this->assertSame('2/3', (string)$r2);
    }

    /**
     * Test round-trip conversion for exact values.
     */
    public function testRoundTripExact(): void
    {
        $r = new Rational(3, 4);
        $f = $r->toFloat();
        $r2 = new Rational($f);

        $this->assertTrue($r->equal($r2));
    }

    /**
     * Test round-trip conversion for approximate values.
     */
    public function testRoundTripApproximate(): void
    {
        $r = new Rational(M_PI);
        $f = $r->toFloat();

        $this->assertEqualsWithDelta(M_PI, $f, 1e-10);
    }

    #endregion

    #region toMixedNumber() tests

    /**
     * Test toMixedNumber with improper positive fraction.
     */
    public function testToMixedNumberPositiveImproper(): void
    {
        $r = new Rational(7, 4);
        [$integer, $fraction] = $r->toMixedNumber();

        $this->assertSame(1, $integer);
        $this->assertSame(3, $fraction->numerator);
        $this->assertSame(4, $fraction->denominator);
    }

    /**
     * Test toMixedNumber with improper negative fraction.
     */
    public function testToMixedNumberNegativeImproper(): void
    {
        $r = new Rational(-7, 4);
        [$integer, $fraction] = $r->toMixedNumber();

        // -7/4 = -1 + (-3/4) (trunc/frac semantics)
        $this->assertSame(-1, $integer);
        $this->assertSame(-3, $fraction->numerator);
        $this->assertSame(4, $fraction->denominator);
    }

    /**
     * Test toMixedNumber with proper fraction (no integer part).
     */
    public function testToMixedNumberProperFraction(): void
    {
        $r = new Rational(3, 4);
        [$integer, $fraction] = $r->toMixedNumber();

        $this->assertSame(0, $integer);
        $this->assertSame(3, $fraction->numerator);
        $this->assertSame(4, $fraction->denominator);
    }

    /**
     * Test toMixedNumber with negative proper fraction.
     */
    public function testToMixedNumberNegativeProperFraction(): void
    {
        $r = new Rational(-3, 4);
        [$integer, $fraction] = $r->toMixedNumber();

        // -3/4 = 0 + (-3/4) (trunc/frac semantics)
        $this->assertSame(0, $integer);
        $this->assertSame(-3, $fraction->numerator);
        $this->assertSame(4, $fraction->denominator);
    }

    /**
     * Test toMixedNumber with whole number.
     */
    public function testToMixedNumberWholeNumber(): void
    {
        $r = new Rational(5);
        [$integer, $fraction] = $r->toMixedNumber();

        $this->assertSame(5, $integer);
        $this->assertSame(0, $fraction->numerator);
    }

    /**
     * Test toMixedNumber with zero.
     */
    public function testToMixedNumberZero(): void
    {
        $r = new Rational(0);
        [$integer, $fraction] = $r->toMixedNumber();

        $this->assertSame(0, $integer);
        $this->assertSame(0, $fraction->numerator);
    }

    /**
     * Test that integer + fraction equals original value.
     */
    public function testToMixedNumberRoundTrip(): void
    {
        $r = new Rational(-11, 3);
        [$integer, $fraction] = $r->toMixedNumber();

        // Reconstruct: integer + fraction should equal original
        $reconstructed = $fraction->add($integer);
        $this->assertTrue($r->equal($reconstructed));
    }

    #endregion
}
