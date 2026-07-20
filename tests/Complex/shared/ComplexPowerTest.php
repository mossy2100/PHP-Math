<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Math\M_I;

#[CoversClass(Complex::class)]
class ComplexPowerTest extends TestCase
{
    #region Method pow() tests.

    /**
     * Test power with integer exponents.
     */
    public function testPowInteger(): void
    {
        // (3 + 4i)^2
        $z = new Complex(3, 4);
        $result = $z->pow(2);

        // (3 + 4i)^2 = 9 + 24i + 16i² = 9 + 24i - 16 = -7 + 24i
        $this->assertEqualsWithDelta(-7.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(24.0, $result->imaginary, EPSILON);

        // z^0 = 1
        $result2 = $z->pow(0);
        $this->assertEqualsWithDelta(1.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);

        // z^1 = z
        $result3 = $z->pow(1);
        $this->assertEqualsWithDelta(3.0, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(4.0, $result3->imaginary, EPSILON);
    }

    /**
     * Test pow(1) returns a new instance, not $this.
     */
    public function testPowOneReturnsNewInstance(): void
    {
        $z = new Complex(3, 4);
        $result = $z->pow(1);

        $this->assertNotSame($z, $result);
        $this->assertEqualsWithDelta($z->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $result->imaginary, EPSILON);
    }

    /**
     * Test i^2 = -1 using pow(2), which has a special-case shortcut.
     */
    public function testISquaredViaPow(): void
    {
        $result = M_I->pow(2);

        $this->assertEqualsWithDelta(-1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);
    }

    /**
     * Test pow(-1) delegates to inv().
     */
    public function testPowNegativeOne(): void
    {
        // (3 + 4i)^(-1) = 1/(3 + 4i) = (3 - 4i)/25
        $z = new Complex(3, 4);
        $result = $z->pow(-1);
        $expected = $z->inv();

        $this->assertEqualsWithDelta($expected->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, EPSILON);

        // Verify actual values: 3/25 - 4i/25
        $this->assertEqualsWithDelta(0.12, $result->real, EPSILON);
        $this->assertEqualsWithDelta(-0.16, $result->imaginary, EPSILON);
    }

    /**
     * Test e^w shortcut.
     */
    public function testPowEBase(): void
    {
        $w = new Complex(2, 3);
        $result = new Complex(M_E)->pow($w);

        // e^(2+3i) should equal exp(2+3i)
        $expected = $w->exp();

        $this->assertEqualsWithDelta($expected->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, EPSILON);
    }

    /**
     * Test 0^0 returns 1 (conventional).
     */
    public function testZeroPowerZero(): void
    {
        $result = new Complex(0)->pow(0);

        $this->assertEqualsWithDelta(1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);
    }

    /**
     * Test 0 raised to positive real returns 0.
     */
    public function testZeroPowerPositive(): void
    {
        $result = new Complex(0)->pow(5);

        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);
    }

    /**
     * Test 0 raised to negative real throws exception.
     */
    public function testZeroPowerNegative(): void
    {
        $this->expectException(DomainException::class);
        new Complex(0)->pow(-2);
    }

    /**
     * Test 0 raised to complex throws exception.
     */
    public function testZeroPowerComplex(): void
    {
        $this->expectException(DomainException::class);
        new Complex(0)->pow(new Complex(1, 1));
    }

    #endregion

    #region Method sqr() tests.

    /**
     * Test i^2 = -1 using sqr().
     */
    public function testISquared(): void
    {
        $result = M_I->sqr();

        $this->assertEqualsWithDelta(-1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);
    }

    /**
     * Test sqr() squares a complex number.
     */
    public function testSqr(): void
    {
        // (3 + 4i)² = 9 + 24i - 16 = -7 + 24i
        $z = new Complex(3, 4);
        $result = $z->sqr();

        $this->assertEqualsWithDelta(-7.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(24.0, $result->imaginary, EPSILON);
    }

    /**
     * Test sqr() with a purely imaginary number.
     */
    public function testSqrImaginary(): void
    {
        // (2i)² = -4
        $z = new Complex(0, 2);
        $result = $z->sqr();

        $this->assertEqualsWithDelta(-4.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);
    }

    /**
     * Test sqr() is equivalent to pow(2).
     */
    public function testSqrEqualsPowTwo(): void
    {
        $z = new Complex(5, -3);
        $sqr = $z->sqr();
        $pow2 = $z->pow(2);

        $this->assertEqualsWithDelta($sqr->real, $pow2->real, EPSILON);
        $this->assertEqualsWithDelta($sqr->imaginary, $pow2->imaginary, EPSILON);
    }

    #endregion
}
