<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Rational;

use Mossy2100\Core\Exceptions\ArithmeticException;
use Mossy2100\Math\Rational;
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
        $r1 = new Rational(3, 4);
        $result = $r1->abs();

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
        $r1 = new Rational(3, 4);
        $result = $r1->neg();

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

    #region Method recip() tests.

    /**
     * Test reciprocal (inverse).
     */
    public function testRecip(): void
    {
        // recip(3/4) = 4/3
        $r1 = new Rational(3, 4);
        $result = $r1->recip();

        $this->assertSame(4, $result->numerator);
        $this->assertSame(3, $result->denominator);

        // recip(-2/5) = -5/2
        $r2 = new Rational(-2, 5);
        $result2 = $r2->recip();

        $this->assertSame(-5, $result2->numerator);
        $this->assertSame(2, $result2->denominator);

        // recip(5) = 1/5
        $r3 = new Rational(5);
        $result3 = $r3->recip();

        $this->assertSame(1, $result3->numerator);
        $this->assertSame(5, $result3->denominator);
    }

    /**
     * Test reciprocal of zero throws exception.
     */
    public function testRecipZeroThrows(): void
    {
        $this->expectException(ArithmeticException::class);
        $r = new Rational(0);
        $r->recip();
    }

    /**
     * Test recip() does not modify the original (immutability).
     */
    public function testRecipDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->recip();

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion
}
