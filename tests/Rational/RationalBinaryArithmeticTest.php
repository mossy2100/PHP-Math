<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalBinaryArithmeticTest extends TestCase
{
    #region Method add() tests.

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
     * Test add() does not modify the original (immutability).
     */
    public function testAddDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r2 = $r->add(new Rational(1, 4));

        $this->assertNotSame($r, $r2);
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
        $this->assertSame(1, $r2->numerator);
        $this->assertSame(1, $r2->denominator);
    }

    #endregion

    #region Method sub() tests.

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
     * Test sub() does not modify the original (immutability).
     */
    public function testSubDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->sub(new Rational(1, 4));

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion

    #region Method mul() tests.

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
     * Test mul() does not modify the original (immutability).
     */
    public function testMulDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->mul(2);

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion

    #region Method div() tests.

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
     * Test div() does not modify the original (immutability).
     */
    public function testDivDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->div(2);

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion
}
