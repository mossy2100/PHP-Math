<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalInspectionTest extends TestCase
{
    #region Method isInt() tests.

    /**
     * Test isInt for values with a denominator of 1.
     */
    public function testIsIntTrue(): void
    {
        $r1 = new Rational(5);
        $this->assertTrue($r1->isInt());

        $r2 = new Rational(-3);
        $this->assertTrue($r2->isInt());

        $r3 = new Rational(0);
        $this->assertTrue($r3->isInt());

        // Reduces to 2/1.
        $r4 = new Rational(4, 2);
        $this->assertTrue($r4->isInt());
    }

    /**
     * Test isInt for values with a denominator other than 1.
     */
    public function testIsIntFalse(): void
    {
        $r1 = new Rational(1, 2);
        $this->assertFalse($r1->isInt());

        $r2 = new Rational(-3, 4);
        $this->assertFalse($r2->isInt());
    }

    #endregion

    #region Method sign() tests.

    /**
     * Test sign for positive values.
     */
    public function testSignPositive(): void
    {
        $r1 = new Rational(3, 4);
        $this->assertSame(1, $r1->sign());

        // Negative numerator and denominator cancel out to a positive value.
        $r2 = new Rational(-3, -4);
        $this->assertSame(1, $r2->sign());
    }

    /**
     * Test sign for negative values.
     */
    public function testSignNegative(): void
    {
        $r1 = new Rational(-3, 4);
        $this->assertSame(-1, $r1->sign());

        // Sign is normalized into the numerator, regardless of which operand was negative.
        $r2 = new Rational(3, -4);
        $this->assertSame(-1, $r2->sign());
    }

    /**
     * Test sign for zero.
     */
    public function testSignZero(): void
    {
        $r = new Rational(0);
        $this->assertSame(0, $r->sign());
    }

    /**
     * Test sign with zeroForZero false: zero returns 1, since a Rational has no -0 equivalent to distinguish
     * (unlike Numbers::sign()'s float case). Non-zero values are unaffected by the parameter.
     */
    public function testSignZeroForZeroFalse(): void
    {
        $this->assertSame(1, new Rational(0)->sign(false));
        $this->assertSame(1, new Rational(3, 4)->sign(false));
        $this->assertSame(-1, new Rational(-3, 4)->sign(false));
    }

    #endregion
}
