<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalUnaryArithmeticTest extends TestCase
{
    #region Method abs() tests.

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
     * Test abs() does not modify the original (immutability).
     */
    public function testAbsDoesNotMutate(): void
    {
        $r = new Rational(-3, 4);

        $r->abs();

        $this->assertSame(-3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion

    #region Method neg() tests.

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
     * Test neg() does not modify the original (immutability).
     */
    public function testNegDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->neg();

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion

    #region Method inv() tests.

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
     * Test inv() does not modify the original (immutability).
     */
    public function testInvDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->inv();

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion
}
