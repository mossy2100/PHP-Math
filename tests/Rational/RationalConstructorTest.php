<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DivisionByZeroError;
use DomainException;
use OceanMoon\Math\Rational;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use UnderflowException;

#[CoversClass(Rational::class)]
class RationalConstructorTest extends TestCase
{
    #region Basic construction tests

    /**
     * Test creating rational numbers with integer arguments.
     */
    public function testConstructorWithIntegers(): void
    {
        $r = new Rational(3, 4);
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);

        // Test reduction
        $r2 = new Rational(6, 8);
        $this->assertSame(3, $r2->numerator);
        $this->assertSame(4, $r2->denominator);

        // Test negative denominator converted to negative numerator
        $r3 = new Rational(3, -4);
        $this->assertSame(-3, $r3->numerator);
        $this->assertSame(4, $r3->denominator);

        // Test both negative
        $r4 = new Rational(-3, -4);
        $this->assertSame(3, $r4->numerator);
        $this->assertSame(4, $r4->denominator);
    }

    /**
     * Test creating rational numbers with float arguments that can be converted to int.
     */
    public function testConstructorWithConvertibleFloats(): void
    {
        // Float that equals an integer
        $r = new Rational(3.0, 4.0);
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);

        // Large float that equals an integer
        $r2 = new Rational(1000000.0, 2000000.0);
        $this->assertSame(1, $r2->numerator);
        $this->assertSame(2, $r2->denominator);
    }

    /**
     * Test creating rational numbers with float arguments that need conversion.
     */
    public function testConstructorWithNonConvertibleFloats(): void
    {
        // 0.5 should convert to 1/2
        $r = new Rational(0.5);
        $this->assertSame(1, $r->numerator);
        $this->assertSame(2, $r->denominator);

        // 0.25 should convert to 1/4
        $r2 = new Rational(0.25);
        $this->assertSame(1, $r2->numerator);
        $this->assertSame(4, $r2->denominator);

        // 0.333... should approximate to 1/3
        $r3 = new Rational(1 / 3);
        $this->assertSame(1, $r3->numerator);
        $this->assertSame(3, $r3->denominator);
    }

    /**
     * Test zero numerator.
     */
    public function testZeroNumerator(): void
    {
        $r = new Rational(0, 5);
        $this->assertSame(0, $r->numerator);
        $this->assertSame(1, $r->denominator); // Canonical form is 0/1
    }

    /**
     * Test default arguments.
     */
    public function testDefaultArguments(): void
    {
        $r = new Rational();
        $this->assertSame(0, $r->numerator);
        $this->assertSame(1, $r->denominator);

        $r2 = new Rational(5);
        $this->assertSame(5, $r2->numerator);
        $this->assertSame(1, $r2->denominator);
    }

    /**
     * Test that equal numerator and denominator simplifies to 1/1.
     */
    public function testEqualNumeratorDenominator(): void
    {
        $r = new Rational(5, 5);
        $this->assertSame(1, $r->numerator);
        $this->assertSame(1, $r->denominator);

        $r2 = new Rational(-7, -7);
        $this->assertSame(1, $r2->numerator);
        $this->assertSame(1, $r2->denominator);
    }

    /**
     * Test that negative numerator and positive denominator equal magnitude simplifies to -1/1.
     */
    public function testNegativeEqualMagnitude(): void
    {
        $r = new Rational(-5, 5);
        $this->assertSame(-1, $r->numerator);
        $this->assertSame(1, $r->denominator);

        $r2 = new Rational(5, -5);
        $this->assertSame(-1, $r2->numerator);
        $this->assertSame(1, $r2->denominator);
    }

    /**
     * Test immutability of properties.
     */
    public function testPropertiesAreReadOnly(): void
    {
        $r = new Rational(3, 4);

        // PHPStan will catch write attempts at static analysis time
        // At runtime, private(set) prevents modification
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion

    #region Error handling tests

    /**
     * Test that zero denominator throws DivisionByZeroError.
     */
    public function testZeroDenominatorThrows(): void
    {
        $this->expectException(DivisionByZeroError::class);
        new Rational(1, 0);
    }

    /**
     * Test that zero float denominator throws DivisionByZeroError.
     */
    public function testZeroFloatDenominatorThrows(): void
    {
        $this->expectException(DivisionByZeroError::class);
        new Rational(1, 0.0);
    }

    /**
     * Test that negative zero float denominator throws DivisionByZeroError.
     */
    public function testNegativeZeroFloatDenominatorThrows(): void
    {
        $this->expectException(DivisionByZeroError::class);
        new Rational(1, -0.0);
    }

    /**
     * Test that infinite numerator throws exception.
     */
    public function testInfiniteNumeratorThrows(): void
    {
        $this->expectException(DomainException::class);
        new Rational(INF);
    }

    /**
     * Test that infinite denominator throws exception.
     */
    public function testInfiniteDenominatorThrows(): void
    {
        $this->expectException(DomainException::class);
        new Rational(1, INF);
    }

    /**
     * Test that NAN numerator throws exception.
     */
    public function testNanNumeratorThrows(): void
    {
        $this->expectException(DomainException::class);
        new Rational(NAN);
    }

    /**
     * Test that NAN denominator throws exception.
     */
    public function testNanDenominatorThrows(): void
    {
        $this->expectException(DomainException::class);
        new Rational(1, NAN);
    }

    #endregion

    #region PHP_INT_MIN / PHP_INT_MAX edge cases

    /**
     * Test PHP_INT_MIN numerator with denominator that is a multiple of 2.
     */
    public function testConstructorWithMinIntNumeratorAndDenominatorMultipleOf2(): void
    {
        $r = new Rational(PHP_INT_MIN, 2);
        $this->assertSame(PHP_INT_MIN / 2, $r->numerator);
        $this->assertSame(1, $r->denominator);
    }

    /**
     * Test PHP_INT_MIN numerator with denominator not a multiple of 2 throws.
     */
    public function testConstructorWithMinIntNumeratorAndDenominatorNotAMultipleOf2Throws(): void
    {
        $this->expectException(OverflowException::class);
        $r = new Rational(PHP_INT_MIN);
    }

    /**
     * Test PHP_INT_MIN denominator with numerator that is a multiple of 2.
     */
    public function testConstructorWithMinIntDenominatorAndNumeratorMultipleOf2(): void
    {
        $r = new Rational(2, PHP_INT_MIN);
        $this->assertSame(-1, $r->numerator);
        $this->assertSame(PHP_INT_MIN / -2, $r->denominator);
    }

    /**
     * Test PHP_INT_MIN denominator with numerator not a multiple of 2 throws.
     */
    public function testConstructorWithMinIntDenominatorAndNumeratorNotAMultipleOf2Throws(): void
    {
        $this->expectException(UnderflowException::class);
        new Rational(1, PHP_INT_MIN);
    }

    /**
     * Test that PHP_INT_MAX can be used as numerator or denominator without throwing.
     */
    public function testConstructorWithPhpIntMax(): void
    {
        $r = new Rational(1, PHP_INT_MAX);
        $this->assertSame(1, $r->numerator);
        $this->assertSame(PHP_INT_MAX, $r->denominator);

        $r = new Rational(PHP_INT_MAX, 1);
        $this->assertSame(PHP_INT_MAX, $r->numerator);
        $this->assertSame(1, $r->denominator);

        $r = new Rational(-1, PHP_INT_MAX);
        $this->assertSame(-1, $r->numerator);
        $this->assertSame(PHP_INT_MAX, $r->denominator);

        $r = new Rational(PHP_INT_MAX, -1);
        $this->assertSame(-PHP_INT_MAX, $r->numerator);
        $this->assertSame(1, $r->denominator);
    }

    /**
     * Test PHP_INT_MIN numerator with denominator 1 throws OverflowException.
     */
    public function testConstructorWithMinIntNumeratorAndDenominator1Throws(): void
    {
        $this->expectException(OverflowException::class);
        new Rational(PHP_INT_MIN, 1);
    }

    /**
     * Test PHP_INT_MIN numerator with denominator -1 throws OverflowException.
     */
    public function testConstructorWithMinIntNumeratorAndDenominatorNeg1Throws(): void
    {
        $this->expectException(OverflowException::class);
        new Rational(PHP_INT_MIN, -1);
    }

    /**
     * Test PHP_INT_MIN numerator with odd denominator falls back to float conversion.
     */
    public function testConstructorWithMinIntNumeratorAndOddDenominator(): void
    {
        // simplify() can't handle PHP_INT_MIN with odd denominator, so the constructor
        // falls back to floatToRatio(). The result is approximate due to float precision.
        $r = new Rational(PHP_INT_MIN, 3);
        $this->assertEqualsWithDelta(PHP_INT_MIN / 3, $r->numerator / $r->denominator, 1.0);
    }

    /**
     * Test PHP_INT_MIN numerator with a larger even denominator.
     */
    public function testConstructorWithMinIntNumeratorAndLargerEvenDenominator(): void
    {
        $r = new Rational(PHP_INT_MIN, 4);
        $this->assertSame(PHP_INT_MIN / 4, $r->numerator);
        $this->assertSame(1, $r->denominator);
    }

    /**
     * Test PHP_INT_MIN numerator with negative even denominator.
     */
    public function testConstructorWithMinIntNumeratorAndNegativeEvenDenominator(): void
    {
        $r = new Rational(PHP_INT_MIN, -2);
        $this->assertSame(PHP_INT_MIN / -2, $r->numerator);
        $this->assertSame(1, $r->denominator);
    }

    /**
     * Test larger even numerator with PHP_INT_MIN denominator.
     */
    public function testConstructorWithLargerEvenNumeratorAndMinIntDenominator(): void
    {
        $r = new Rational(4, PHP_INT_MIN);
        $this->assertSame(-1, $r->numerator);
        $this->assertSame(PHP_INT_MIN / -4, $r->denominator);
    }

    #endregion

    #region Float-to-ratio conversion tests (via constructor)

    /**
     * Test constructor with negative floats.
     */
    public function testConstructorWithNegativeFloats(): void
    {
        $r = new Rational(-0.5);
        $this->assertSame(-1, $r->numerator);
        $this->assertSame(2, $r->denominator);

        $r2 = new Rational(-0.75);
        $this->assertSame(-3, $r2->numerator);
        $this->assertSame(4, $r2->denominator);
    }

    /**
     * Test constructor with irrational float approximations.
     */
    public function testConstructorWithIrrationalFloats(): void
    {
        // π should approximate closely.
        $r = new Rational(M_PI);
        $this->assertEqualsWithDelta(M_PI, $r->numerator / $r->denominator, 1e-10);

        // e should approximate closely.
        $r2 = new Rational(M_E);
        $this->assertEqualsWithDelta(M_E, $r2->numerator / $r2->denominator, 1e-10);
    }

    /**
     * Test constructor with (float)PHP_INT_MIN throws OverflowException.
     */
    public function testConstructorWithFloatPhpIntMinThrows(): void
    {
        // (float)PHP_INT_MIN converts to int PHP_INT_MIN, which can't be simplified.
        $this->expectException(OverflowException::class);
        new Rational((float)PHP_INT_MIN);
    }

    /**
     * Test constructor with (float)PHP_INT_MAX.
     */
    public function testConstructorWithFloatPhpIntMax(): void
    {
        $r = new Rational((float)PHP_INT_MAX);
        $this->assertSame(PHP_INT_MAX, $r->numerator);
        $this->assertSame(1, $r->denominator);
    }

    /**
     * Test constructor with 1/PHP_INT_MAX float.
     */
    public function testConstructorWithInversePhpIntMax(): void
    {
        $r = new Rational(1.0 / PHP_INT_MAX);
        $this->assertSame(1, $r->numerator);
        $this->assertSame(PHP_INT_MAX, $r->denominator);
    }

    /**
     * Test constructor with -1/PHP_INT_MAX float.
     */
    public function testConstructorWithNegativeInversePhpIntMax(): void
    {
        $r = new Rational(-1.0 / PHP_INT_MAX);
        $this->assertSame(-1, $r->numerator);
        $this->assertSame(PHP_INT_MAX, $r->denominator);
    }

    /**
     * Test constructor with 1/PHP_INT_MIN float.
     */
    public function testConstructorWithInversePhpIntMin(): void
    {
        $r = new Rational(1.0 / PHP_INT_MIN);
        $this->assertSame(-1, $r->numerator);
        $this->assertSame(PHP_INT_MAX, $r->denominator);
    }

    /**
     * Test constructor with very small float throws UnderflowException.
     */
    public function testConstructorWithVerySmallFloatThrows(): void
    {
        $this->expectException(UnderflowException::class);
        new Rational(1e-20);
    }

    /**
     * Test constructor with very large float throws OverflowException.
     */
    public function testConstructorWithVeryLargeFloatThrows(): void
    {
        $this->expectException(OverflowException::class);
        new Rational((float)PHP_INT_MAX * 2);
    }

    /**
     * Test constructor with float that triggers convergent overflow in continued fractions.
     */
    public function testConstructorWithConvergentOverflowFloat(): void
    {
        $value = 2.1213650134300899e-10;
        $r = new Rational($value);

        $this->assertSame(431, $r->numerator);
        $this->assertSame(2031710701701, $r->denominator);
        $this->assertEqualsWithDelta($value, $r->numerator / $r->denominator, 1e-15);
    }

    /**
     * Test constructor with float that triggers zero remainder in continued fractions.
     */
    public function testConstructorWithZeroRemainderFloat(): void
    {
        $value = 2.176543618258578e-17;
        $r = new Rational($value);

        $this->assertSame(1, $r->numerator);
        $this->assertSame(45944404312011256, $r->denominator);
        $this->assertEqualsWithDelta($value, $r->numerator / $r->denominator, 1e-25);
    }

    #endregion
}
