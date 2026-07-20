<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RoundingMode;

#[CoversClass(Complex::class)]
class ComplexRoundingTest extends TestCase
{
    #region Method round() tests.

    /**
     * Test rounding with the default precision (0) and default mode (HalfAwayFromZero).
     */
    public function testRoundDefaultPrecisionZero(): void
    {
        // round(7/3 + 8/3 i) = 2 + 3i (2.333... rounds down, 2.666... rounds up)
        $z = new Complex(7 / 3, 8 / 3);
        $result = $z->round(0);

        $this->assertSame(2.0, $result->real);
        $this->assertSame(3.0, $result->imaginary);
    }

    /**
     * Test rounding at exact ties (x.5) with the default mode, which rounds away from zero.
     */
    public function testRoundDefaultModeTies(): void
    {
        // round(2.5 - 2.5i) = 3 - 3i (both parts round away from zero)
        $z = new Complex(2.5, -2.5);
        $result = $z->round(0);

        $this->assertSame(3.0, $result->real);
        $this->assertSame(-3.0, $result->imaginary);
    }

    /**
     * Test rounding with a positive precision.
     */
    public function testRoundWithPrecision(): void
    {
        $z = new Complex(1.2345, -1.2345);
        $result = $z->round(2);

        $this->assertSame(1.23, $result->real);
        $this->assertSame(-1.23, $result->imaginary);
    }

    /**
     * Test round() with RoundingMode::TowardsZero: always truncates, ignoring the remainder.
     */
    public function testRoundTowardsZero(): void
    {
        $z = new Complex(7 / 3, -7 / 3);
        $result = $z->round(0, RoundingMode::TowardsZero);

        $this->assertSame(2.0, $result->real);
        $this->assertSame(-2.0, $result->imaginary);
    }

    /**
     * Test round() with RoundingMode::AwayFromZero: always rounds away from zero when there's a remainder.
     */
    public function testRoundAwayFromZero(): void
    {
        $z = new Complex(7 / 3, -7 / 3);
        $result = $z->round(0, RoundingMode::AwayFromZero);

        $this->assertSame(3.0, $result->real);
        $this->assertSame(-3.0, $result->imaginary);
    }

    /**
     * Test round() with RoundingMode::NegativeInfinity: equivalent to floor() for each part.
     */
    public function testRoundNegativeInfinity(): void
    {
        $z = new Complex(7 / 3, -7 / 3);
        $result = $z->round(0, RoundingMode::NegativeInfinity);

        $this->assertSame(2.0, $result->real);
        $this->assertSame(-3.0, $result->imaginary);
    }

    /**
     * Test round() with RoundingMode::PositiveInfinity: equivalent to ceil() for each part.
     */
    public function testRoundPositiveInfinity(): void
    {
        $z = new Complex(7 / 3, -7 / 3);
        $result = $z->round(0, RoundingMode::PositiveInfinity);

        $this->assertSame(3.0, $result->real);
        $this->assertSame(-2.0, $result->imaginary);
    }

    /**
     * Test round() with RoundingMode::HalfTowardsZero: exact ties round toward zero, unlike the default
     * HalfAwayFromZero mode.
     */
    public function testRoundHalfTowardsZero(): void
    {
        $z = new Complex(2.5, -2.5);
        $result = $z->round(0, RoundingMode::HalfTowardsZero);

        $this->assertSame(2.0, $result->real);
        $this->assertSame(-2.0, $result->imaginary);
    }

    /**
     * Test round() with RoundingMode::HalfEven ("banker's rounding"): exact ties round to the nearest even
     * integer.
     */
    public function testRoundHalfEven(): void
    {
        $z = new Complex(0.5, 1.5);
        $result = $z->round(0, RoundingMode::HalfEven);

        $this->assertSame(0.0, $result->real);
        $this->assertSame(2.0, $result->imaginary);
    }

    /**
     * Test round() with RoundingMode::HalfOdd: exact ties round to the nearest odd integer.
     */
    public function testRoundHalfOdd(): void
    {
        $z = new Complex(0.5, 1.5);
        $result = $z->round(0, RoundingMode::HalfOdd);

        $this->assertSame(1.0, $result->real);
        $this->assertSame(1.0, $result->imaginary);
    }

    /**
     * Test round() on an already-integral Complex returns the same value for every rounding mode.
     */
    public function testRoundIntegralValueIsModeIndependent(): void
    {
        $z = new Complex(5, -3);
        foreach (RoundingMode::cases() as $mode) {
            $result = $z->round(0, $mode);
            $this->assertSame(5.0, $result->real);
            $this->assertSame(-3.0, $result->imaginary);
        }
    }

    /**
     * Test round() returns a new instance, not $this (immutability).
     */
    public function testRoundReturnsNewInstance(): void
    {
        $z = new Complex(1.5, 2.5);
        $result = $z->round(0);

        $this->assertNotSame($z, $result);
        $this->assertSame(1.5, $z->real);
        $this->assertSame(2.5, $z->imaginary);
    }

    /**
     * Test round() with a negative precision rounds to the nearest power of ten before the decimal
     * point (e.g. -1 = nearest ten, -2 = nearest hundred), matching PHP's own round().
     */
    public function testRoundNegativePrecision(): void
    {
        $z = new Complex(1234.5, 5678.9);

        $this->assertSame(1200.0, $z->round(-2)->real);
        $this->assertSame(5700.0, $z->round(-2)->imaginary);

        $this->assertSame(1230.0, $z->round(-1)->real);
        $this->assertSame(5680.0, $z->round(-1)->imaginary);
    }

    /**
     * Test round() with no arguments uses the default precision (0) and mode (HalfAwayFromZero).
     */
    public function testRoundNoArguments(): void
    {
        $z = new Complex(2.5, -2.5);
        $result = $z->round();

        $this->assertSame(3.0, $result->real);
        $this->assertSame(-3.0, $result->imaginary);
    }

    #endregion
}
