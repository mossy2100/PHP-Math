<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Math\Complex;
use OverflowException;
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
     * Test pow() with an odd integer exponent, exercising the exponentiation-by-squaring path rather than one of
     * the dedicated 0/1/2/-1 shortcuts.
     */
    public function testPowOddIntegerExponent(): void
    {
        // (1 + i)^3 = -2 + 2i
        $z = new Complex(1, 1);
        $result = $z->pow(3);

        $this->assertEqualsWithDelta(-2.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(2.0, $result->imaginary, EPSILON);
    }

    /**
     * Test pow() with an even integer exponent, exercising the exponentiation-by-squaring path rather than one of
     * the dedicated 0/1/2/-1 shortcuts.
     */
    public function testPowEvenIntegerExponent(): void
    {
        // (1 + i)^4 = ((1 + i)^2)^2 = (2i)^2 = -4
        $z = new Complex(1, 1);
        $result = $z->pow(4);

        $this->assertEqualsWithDelta(-4.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);
    }

    /**
     * Test pow() with a larger integer exponent, driving the exponentiation-by-squaring loop through several
     * iterations rather than just one or two.
     */
    public function testPowLargeIntegerExponent(): void
    {
        $z = new Complex(1, 1);

        // (1 + i)^16 = (((1 + i)^2)^2)^2)^2 = ((2i)^2)^2)^2 = ((-4)^2)^2 = 16^2 = 256
        $result = $z->pow(16);
        $this->assertEqualsWithDelta(256.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // (1 + i)^17 = (1 + i)^16 * (1 + i) = 256 * (1 + i) = 256 + 256i
        $result2 = $z->pow(17);
        $this->assertEqualsWithDelta(256.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(256.0, $result2->imaginary, EPSILON);
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
     * Test pow(-1) delegates to recip().
     */
    public function testPowNegativeOne(): void
    {
        // (3 + 4i)^(-1) = 1/(3 + 4i) = (3 - 4i)/25
        $z = new Complex(3, 4);
        $result = $z->pow(-1);
        $expected = $z->recip();

        $this->assertEqualsWithDelta($expected->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, EPSILON);

        // Verify actual values: 3/25 - 4i/25
        $this->assertEqualsWithDelta(0.12, $result->real, EPSILON);
        $this->assertEqualsWithDelta(-0.16, $result->imaginary, EPSILON);
    }

    /**
     * Test pow() with a negative integer exponent whose magnitude is greater than 1, exercising the
     * invert-the-base-first branch of the exponentiation-by-squaring path (rather than the dedicated pow(-1)
     * shortcut).
     */
    public function testPowNegativeIntegerExponent(): void
    {
        // (1 + i)^(-3) = 1 / (-2 + 2i) = -0.25 - 0.25i
        $z = new Complex(1, 1);
        $result = $z->pow(-3);

        $this->assertEqualsWithDelta(-0.25, $result->real, EPSILON);
        $this->assertEqualsWithDelta(-0.25, $result->imaginary, EPSILON);

        // Also verify it equals the reciprocal of the corresponding positive-exponent result.
        $expected = $z->pow(3)->recip();
        $this->assertEqualsWithDelta($expected->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, EPSILON);
    }

    /**
     * Test e^w shortcut.
     */
    public function testPowEBase(): void
    {
        $z = new Complex(2, 3);
        $result = new Complex(M_E)->pow($z);

        // e^(2+3i) should equal exp(2+3i)
        $expected = $z->exp();

        $this->assertEqualsWithDelta($expected->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, EPSILON);
    }

    /**
     * Test an exponent of 0.5 uses sqrt(), so it's exact on the axes.
     */
    public function testPowHalfUsesSqrt(): void
    {
        $result = new Complex(-4)->pow(0.5);
        $this->assertSame(0.0, $result->real);
        $this->assertSame(2.0, $result->imaginary);

        $result = new Complex(3, 4)->pow(0.5);
        $this->assertSame(2.0, $result->real);
        $this->assertSame(1.0, $result->imaginary);
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

    /**
     * Test pow() with an integer exponent throws at the first product that overflows, as it uses exponentiation by
     * squaring.
     */
    public function testPowIntegerOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot raise the given Complex to the given power. The result would overflow.');
        new Complex(10, 1)->pow(1000);
    }

    /**
     * Test pow() with an integer exponent gives exactly the products of exponentiation by squaring.
     */
    public function testPowIntegerUsesSquaring(): void
    {
        $z = new Complex(1.5, -2.25);
        $recip = $z->recip();

        $this->assertTrue($z->pow(3)->equal($z->mul($z->mul($z))));
        $this->assertTrue($z->pow(-2)->equal($recip->mul($recip)));
    }

    /**
     * Test pow() just below the negative real axis gives the principal value from the -π side.
     */
    public function testPowJustBelowNegativeRealAxis(): void
    {
        $result = new Complex(-1, -1e-17)->pow(1 / 3);
        $this->assertEqualsWithDelta(0.5, $result->real, EPSILON);
        $this->assertEqualsWithDelta(-sqrt(3) / 2, $result->imaginary, EPSILON);
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

    /**
     * Test sqr() throws when the result overflows.
     */
    public function testSqrOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot square the given Complex. The result would overflow.');
        new Complex(1e200)->sqr();
    }

    /**
     * Test sqr() gives an accurate real part when the parts are close in size, where a² - b² loses digits.
     */
    public function testSqrPartsCloseInSize(): void
    {
        // The exact real part is 1366.98719999999150..., which a² - b² gives as 1366.9871999999887.
        $result = new Complex(266.21, -263.63)->sqr();

        $this->assertSame(1366.9871999999914, $result->real);
    }

    /**
     * Test sqr()'s imaginary part matches mul() exactly when the product of the parts is subnormal.
     */
    public function testSqrMatchesMulForSubnormalProduct(): void
    {
        $z = new Complex(8.848e-158, 6.487e-158);
        $product = $z->mul($z);

        $this->assertSame($product->imaginary, $z->sqr()->imaginary);
        $this->assertSame($product->imaginary, $z->pow(2)->imaginary);
    }

    #endregion
}
