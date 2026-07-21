<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RoundingMode;

#[CoversClass(Rational::class)]
class RationalRoundingTest extends TestCase
{
    #region Method round() tests.

    /**
     * Test rounding.
     */
    public function testRound(): void
    {
        // round(7/3) = 2 (2.333...)
        $r = new Rational(7, 3);
        $this->assertSame(2, $r->round());

        // round(8/3) = 3 (2.666...)
        $r2 = new Rational(8, 3);
        $this->assertSame(3, $r2->round());

        // round(5/2) = 3 (2.5, rounds away from zero)
        $r3 = new Rational(5, 2);
        $this->assertSame(3, $r3->round());

        // round(-5/2) = -3 (-2.5, rounds away from zero)
        $r4 = new Rational(-5, 2);
        $this->assertSame(-3, $r4->round());

        // round(5) = 5
        $r5 = new Rational(5);
        $this->assertSame(5, $r5->round());
    }

    /**
     * Test round() with RoundingMode::TowardsZero: always truncates, ignoring the remainder.
     */
    public function testRoundTowardsZero(): void
    {
        $this->assertSame(2, new Rational(7, 3)->round(RoundingMode::TowardsZero));
        $this->assertSame(-2, new Rational(-7, 3)->round(RoundingMode::TowardsZero));
        $this->assertSame(2, new Rational(5, 2)->round(RoundingMode::TowardsZero));
        $this->assertSame(-2, new Rational(-5, 2)->round(RoundingMode::TowardsZero));
    }

    /**
     * Test round() with RoundingMode::AwayFromZero: always rounds away from zero when there's a remainder.
     */
    public function testRoundAwayFromZero(): void
    {
        $this->assertSame(3, new Rational(7, 3)->round(RoundingMode::AwayFromZero));
        $this->assertSame(-3, new Rational(-7, 3)->round(RoundingMode::AwayFromZero));
        $this->assertSame(3, new Rational(5, 2)->round(RoundingMode::AwayFromZero));
        $this->assertSame(-3, new Rational(-5, 2)->round(RoundingMode::AwayFromZero));
    }

    /**
     * Test round() with RoundingMode::NegativeInfinity: equivalent to floor().
     */
    public function testRoundNegativeInfinity(): void
    {
        $this->assertSame(2, new Rational(7, 3)->round(RoundingMode::NegativeInfinity));
        $this->assertSame(-3, new Rational(-7, 3)->round(RoundingMode::NegativeInfinity));
        $this->assertSame(2, new Rational(5, 2)->round(RoundingMode::NegativeInfinity));
        $this->assertSame(-3, new Rational(-5, 2)->round(RoundingMode::NegativeInfinity));
    }

    /**
     * Test round() with RoundingMode::PositiveInfinity: equivalent to ceil().
     */
    public function testRoundPositiveInfinity(): void
    {
        $this->assertSame(3, new Rational(7, 3)->round(RoundingMode::PositiveInfinity));
        $this->assertSame(-2, new Rational(-7, 3)->round(RoundingMode::PositiveInfinity));
        $this->assertSame(3, new Rational(5, 2)->round(RoundingMode::PositiveInfinity));
        $this->assertSame(-2, new Rational(-5, 2)->round(RoundingMode::PositiveInfinity));
    }

    /**
     * Test round() with RoundingMode::HalfTowardsZero: exact ties round toward zero, unlike the default
     * HalfAwayFromZero mode.
     */
    public function testRoundHalfTowardsZero(): void
    {
        // Exact ties round toward zero.
        $this->assertSame(2, new Rational(5, 2)->round(RoundingMode::HalfTowardsZero));
        $this->assertSame(-2, new Rational(-5, 2)->round(RoundingMode::HalfTowardsZero));

        // Non-tie values round the same as HalfAwayFromZero.
        $this->assertSame(2, new Rational(7, 3)->round(RoundingMode::HalfTowardsZero));
        $this->assertSame(-2, new Rational(-7, 3)->round(RoundingMode::HalfTowardsZero));
    }

    /**
     * Test round() with RoundingMode::HalfEven ("banker's rounding"): exact ties round to the nearest even
     * integer.
     */
    public function testRoundHalfEven(): void
    {
        $this->assertSame(0, new Rational(1, 2)->round(RoundingMode::HalfEven));  // 0.5 -> 0 (even)
        $this->assertSame(2, new Rational(3, 2)->round(RoundingMode::HalfEven));  // 1.5 -> 2 (even)
        $this->assertSame(2, new Rational(5, 2)->round(RoundingMode::HalfEven));  // 2.5 -> 2 (even)
        $this->assertSame(4, new Rational(7, 2)->round(RoundingMode::HalfEven));  // 3.5 -> 4 (even)
        $this->assertSame(-2, new Rational(-5, 2)->round(RoundingMode::HalfEven)); // -2.5 -> -2 (even)
    }

    /**
     * Test round() with RoundingMode::HalfOdd: exact ties round to the nearest odd integer.
     */
    public function testRoundHalfOdd(): void
    {
        $this->assertSame(1, new Rational(1, 2)->round(RoundingMode::HalfOdd));  // 0.5 -> 1 (odd)
        $this->assertSame(1, new Rational(3, 2)->round(RoundingMode::HalfOdd));  // 1.5 -> 1 (odd)
        $this->assertSame(3, new Rational(5, 2)->round(RoundingMode::HalfOdd));  // 2.5 -> 3 (odd)
        $this->assertSame(3, new Rational(7, 2)->round(RoundingMode::HalfOdd));  // 3.5 -> 3 (odd)
        $this->assertSame(-3, new Rational(-5, 2)->round(RoundingMode::HalfOdd)); // -2.5 -> -3 (odd)
    }

    /**
     * Test round() stays exact for values with a numerator large enough that converting to float first (as a
     * naive implementation via PHP's own round() might) would lose precision and produce the wrong answer.
     */
    public function testRoundExactForLargeNumerator(): void
    {
        $r = new Rational(PHP_INT_MAX - 1, 3);
        $this->assertSame(intdiv(PHP_INT_MAX - 1, 3), $r->round());
    }

    /**
     * Test round() on an already-integral Rational returns the same value for every rounding mode.
     */
    public function testRoundIntegralValueIsModeIndependent(): void
    {
        $r = new Rational(5);
        foreach (RoundingMode::cases() as $mode) {
            $this->assertSame(5, $r->round($mode));
        }
    }

    #endregion

    #region Method floor() tests.

    /**
     * Test floor.
     */
    public function testFloor(): void
    {
        // floor(7/3) = 2
        $r = new Rational(7, 3);
        $this->assertSame(2, $r->floor());

        // floor(-7/3) = -3
        $r2 = new Rational(-7, 3);
        $this->assertSame(-3, $r2->floor());

        // floor(5) = 5
        $r3 = new Rational(5);
        $this->assertSame(5, $r3->floor());

        // floor(0) = 0
        $r4 = new Rational(0);
        $this->assertSame(0, $r4->floor());
    }

    #endregion

    #region Method ceil() tests.

    /**
     * Test ceiling.
     */
    public function testCeil(): void
    {
        // ceil(7/3) = 3
        $r = new Rational(7, 3);
        $this->assertSame(3, $r->ceil());

        // ceil(-7/3) = -2
        $r2 = new Rational(-7, 3);
        $this->assertSame(-2, $r2->ceil());

        // ceil(5) = 5
        $r3 = new Rational(5);
        $this->assertSame(5, $r3->ceil());

        // ceil(0) = 0
        $r4 = new Rational(0);
        $this->assertSame(0, $r4->ceil());
    }

    #endregion
}
