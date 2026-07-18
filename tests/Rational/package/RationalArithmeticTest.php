<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DomainException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RoundingMode;

#[CoversClass(Rational::class)]
class RationalArithmeticTest extends TestCase
{
    /**
     * Test negation.
     */
    public function testNeg(): void
    {
        $r = new Rational(3, 4);
        $result = $r->neg();

        $this->assertSame(-3, $result->numerator);
        $this->assertSame(4, $result->denominator);

        // Double negation
        $result2 = $result->neg();
        $this->assertSame(3, $result2->numerator);
        $this->assertSame(4, $result2->denominator);

        // Negate zero
        $r2 = new Rational(0);
        $result3 = $r2->neg();
        $this->assertSame(0, $result3->numerator);
        $this->assertSame(1, $result3->denominator);
    }

    /**
     * Test addition with Rational.
     */
    public function testAddRational(): void
    {
        // 1/2 + 1/3 = 5/6
        $r1 = new Rational(1, 2);
        $r2 = new Rational(1, 3);
        $result = $r1->add($r2);

        $this->assertSame(5, $result->numerator);
        $this->assertSame(6, $result->denominator);

        // 3/4 + 1/4 = 1
        $r3 = new Rational(3, 4);
        $r4 = new Rational(1, 4);
        $result2 = $r3->add($r4);

        $this->assertSame(1, $result2->numerator);
        $this->assertSame(1, $result2->denominator);
    }

    /**
     * Test addition with integer.
     */
    public function testAddInteger(): void
    {
        // 1/2 + 2 = 5/2
        $r = new Rational(1, 2);
        $result = $r->add(2);

        $this->assertSame(5, $result->numerator);
        $this->assertSame(2, $result->denominator);
    }

    /**
     * Test subtraction with Rational.
     */
    public function testSubRational(): void
    {
        // 3/4 - 1/4 = 1/2
        $r1 = new Rational(3, 4);
        $r2 = new Rational(1, 4);
        $result = $r1->sub($r2);

        $this->assertSame(1, $result->numerator);
        $this->assertSame(2, $result->denominator);

        // 1/2 - 3/4 = -1/4
        $r3 = new Rational(1, 2);
        $r4 = new Rational(3, 4);
        $result2 = $r3->sub($r4);

        $this->assertSame(-1, $result2->numerator);
        $this->assertSame(4, $result2->denominator);
    }

    /**
     * Test subtraction with integer.
     */
    public function testSubInteger(): void
    {
        // 5/2 - 2 = 1/2
        $r = new Rational(5, 2);
        $result = $r->sub(2);

        $this->assertSame(1, $result->numerator);
        $this->assertSame(2, $result->denominator);
    }

    /**
     * Test reciprocal (inverse).
     */
    public function testInv(): void
    {
        // inv(3/4) = 4/3
        $r = new Rational(3, 4);
        $result = $r->inv();

        $this->assertSame(4, $result->numerator);
        $this->assertSame(3, $result->denominator);

        // inv(-2/5) = -5/2
        $r2 = new Rational(-2, 5);
        $result2 = $r2->inv();

        $this->assertSame(-5, $result2->numerator);
        $this->assertSame(2, $result2->denominator);

        // inv(5) = 1/5
        $r3 = new Rational(5);
        $result3 = $r3->inv();

        $this->assertSame(1, $result3->numerator);
        $this->assertSame(5, $result3->denominator);
    }

    /**
     * Test reciprocal of zero throws exception.
     */
    public function testInvZeroThrows(): void
    {
        $this->expectException(ArithmeticException::class);
        $r = new Rational(0);
        $r->inv();
    }

    /**
     * Test multiplication with Rational.
     */
    public function testMulRational(): void
    {
        // 2/3 * 3/4 = 1/2
        $r1 = new Rational(2, 3);
        $r2 = new Rational(3, 4);
        $result = $r1->mul($r2);

        $this->assertSame(1, $result->numerator);
        $this->assertSame(2, $result->denominator);

        // 3/5 * 5/7 = 3/7
        $r3 = new Rational(3, 5);
        $r4 = new Rational(5, 7);
        $result2 = $r3->mul($r4);

        $this->assertSame(3, $result2->numerator);
        $this->assertSame(7, $result2->denominator);
    }

    /**
     * Test multiplication with integer.
     */
    public function testMulInteger(): void
    {
        // 2/3 * 6 = 4
        $r = new Rational(2, 3);
        $result = $r->mul(6);

        $this->assertSame(4, $result->numerator);
        $this->assertSame(1, $result->denominator);
    }

    /**
     * Test multiplication with zero.
     */
    public function testMulZero(): void
    {
        $r = new Rational(3, 4);
        $result = $r->mul(0);

        $this->assertSame(0, $result->numerator);
        $this->assertSame(1, $result->denominator);
    }

    /**
     * Test division with Rational.
     */
    public function testDivRational(): void
    {
        // (2/3) / (3/4) = 8/9
        $r1 = new Rational(2, 3);
        $r2 = new Rational(3, 4);
        $result = $r1->div($r2);

        $this->assertSame(8, $result->numerator);
        $this->assertSame(9, $result->denominator);
    }

    /**
     * Test division with integer.
     */
    public function testDivInteger(): void
    {
        // (3/4) / 2 = 3/8
        $r = new Rational(3, 4);
        $result = $r->div(2);

        $this->assertSame(3, $result->numerator);
        $this->assertSame(8, $result->denominator);
    }

    /**
     * Test dividing zero by a non-zero value short-circuits to 0, without going through the
     * general cross-cancellation path.
     */
    public function testDivZeroNumerator(): void
    {
        // (0/7) / 3 = 0/1
        $r = new Rational(0, 7);
        $result = $r->div(3);

        $this->assertSame(0, $result->numerator);
        $this->assertSame(1, $result->denominator);
    }

    /**
     * Test division where the numerators share a common factor, exercising the gcd(a,c)
     * cross-cancellation branch.
     */
    public function testDivCancelsCommonNumeratorFactor(): void
    {
        // (4/5) / (6/7): numerators 4 and 6 share a factor of 2, so gcd(a,c) = 2.
        // Cross-cancelling gives (2/5) * (7/3) = 14/15.
        $r1 = new Rational(4, 5);
        $r2 = new Rational(6, 7);
        $result = $r1->div($r2);

        $this->assertSame(14, $result->numerator);
        $this->assertSame(15, $result->denominator);
    }

    /**
     * Test division where the denominators share a common factor, exercising the gcd(b,d)
     * cross-cancellation branch.
     */
    public function testDivCancelsCommonDenominatorFactor(): void
    {
        // (3/4) / (5/6): denominators 4 and 6 share a factor of 2, so gcd(b,d) = 2.
        // Cross-cancelling gives (3/2) * (3/5) = 9/10.
        $r1 = new Rational(3, 4);
        $r2 = new Rational(5, 6);
        $result = $r1->div($r2);

        $this->assertSame(9, $result->numerator);
        $this->assertSame(10, $result->denominator);
    }

    /**
     * Test division by zero throws exception.
     */
    public function testDivZeroThrows(): void
    {
        $this->expectException(ArithmeticException::class);
        $r = new Rational(3, 4);
        $r->div(0);
    }

    /**
     * Test power with positive exponent.
     */
    public function testPowPositive(): void
    {
        // (2/3)^2 = 4/9
        $r = new Rational(2, 3);
        $result = $r->pow(2);

        $this->assertSame(4, $result->numerator);
        $this->assertSame(9, $result->denominator);

        // (1/2)^3 = 1/8
        $r2 = new Rational(1, 2);
        $result2 = $r2->pow(3);

        $this->assertSame(1, $result2->numerator);
        $this->assertSame(8, $result2->denominator);
    }

    /**
     * Test power with zero exponent.
     */
    public function testPowZero(): void
    {
        // (3/4)^0 = 1
        $r = new Rational(3, 4);
        $result = $r->pow(0);

        $this->assertSame(1, $result->numerator);
        $this->assertSame(1, $result->denominator);

        // 0^0 = 1 (by convention)
        $r2 = new Rational(0);
        $result2 = $r2->pow(0);

        $this->assertSame(1, $result2->numerator);
        $this->assertSame(1, $result2->denominator);
    }

    /**
     * Test power with exponent 1 returns an equal but distinct Rational (a clone, not the same instance).
     */
    public function testPowOne(): void
    {
        // (3/4)^1 = 3/4
        $r = new Rational(3, 4);
        $result = $r->pow(1);

        $this->assertSame(3, $result->numerator);
        $this->assertSame(4, $result->denominator);
        $this->assertNotSame($r, $result);
    }

    /**
     * Test power with exponent -1 delegates to inv().
     */
    public function testPowNegativeOne(): void
    {
        // (3/4)^(-1) = 4/3
        $r = new Rational(3, 4);
        $result = $r->pow(-1);

        $this->assertSame(4, $result->numerator);
        $this->assertSame(3, $result->denominator);

        // (-2/5)^(-1) = -5/2
        $r2 = new Rational(-2, 5);
        $result2 = $r2->pow(-1);

        $this->assertSame(-5, $result2->numerator);
        $this->assertSame(2, $result2->denominator);
    }

    /**
     * Test power with negative exponent.
     */
    public function testPowNegative(): void
    {
        // (2/3)^-2 = 9/4
        $r = new Rational(2, 3);
        $result = $r->pow(-2);

        $this->assertSame(9, $result->numerator);
        $this->assertSame(4, $result->denominator);
    }

    /**
     * Test power with exponent PHP_INT_MIN doesn't overflow when negating the exponent.
     *
     * Negative exponents are normally handled via inv()->pow(-$exponent), but negating PHP_INT_MIN overflows to a
     * float in PHP, which would previously cause a TypeError when passed to the int-typed recursive pow() call.
     * Base ±1 is used because any other base would genuinely overflow an int at this magnitude of exponent.
     */
    public function testPowIntMinExponent(): void
    {
        // 1^PHP_INT_MIN = 1
        $r = new Rational(1);
        $result = $r->pow(PHP_INT_MIN);

        $this->assertSame(1, $result->numerator);
        $this->assertSame(1, $result->denominator);

        // (-1)^PHP_INT_MIN = 1, since PHP_INT_MIN is even
        $r2 = new Rational(-1);
        $result2 = $r2->pow(PHP_INT_MIN);

        $this->assertSame(1, $result2->numerator);
        $this->assertSame(1, $result2->denominator);
    }

    /**
     * Test zero to positive power returns zero.
     */
    public function testPowZeroPositive(): void
    {
        // 0^1 = 0
        $r = new Rational(0);
        $result = $r->pow(1);

        $this->assertSame(0, $result->numerator);
        $this->assertSame(1, $result->denominator);

        // 0^5 = 0
        $result2 = $r->pow(5);

        $this->assertSame(0, $result2->numerator);
        $this->assertSame(1, $result2->denominator);
    }

    /**
     * Test zero to negative power throws exception.
     */
    public function testPowZeroNegativeThrows(): void
    {
        $this->expectException(DomainException::class);
        $r = new Rational(0);
        $r->pow(-1);
    }

    /**
     * Test sqr() squares a rational number.
     */
    public function testSqr(): void
    {
        // (3/4)² = 9/16
        $r = new Rational(3, 4);
        $result = $r->sqr();
        $this->assertSame(9, $result->numerator);
        $this->assertSame(16, $result->denominator);
    }

    /**
     * Test sqr() with a negative rational number.
     */
    public function testSqrNegative(): void
    {
        // (-2/3)² = 4/9
        $r = new Rational(-2, 3);
        $result = $r->sqr();
        $this->assertSame(4, $result->numerator);
        $this->assertSame(9, $result->denominator);
    }

    /**
     * Test sqr() is equivalent to pow(2).
     */
    public function testSqrEqualsPowTwo(): void
    {
        $r = new Rational(5, 7);
        $this->assertTrue($r->sqr()->equal($r->pow(2)));
    }

    /**
     * Test absolute value.
     */
    public function testAbs(): void
    {
        // abs(3/4) = 3/4
        $r = new Rational(3, 4);
        $result = $r->abs();

        $this->assertSame(3, $result->numerator);
        $this->assertSame(4, $result->denominator);

        // abs(-3/4) = 3/4
        $r2 = new Rational(-3, 4);
        $result2 = $r2->abs();

        $this->assertSame(3, $result2->numerator);
        $this->assertSame(4, $result2->denominator);

        // abs(0) = 0
        $r3 = new Rational(0);
        $result3 = $r3->abs();

        $this->assertSame(0, $result3->numerator);
        $this->assertSame(1, $result3->denominator);
    }

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

    /**
     * Test immutability - operations return new instances.
     */
    public function testImmutability(): void
    {
        $r = new Rational(3, 4);
        $r2 = $r->add(new Rational(1, 4));

        $this->assertNotSame($r, $r2);
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
        $this->assertSame(1, $r2->numerator);
        $this->assertSame(1, $r2->denominator);
    }
}
