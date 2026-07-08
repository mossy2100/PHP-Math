<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DivisionByZeroError;
use OceanMoon\Math\Rational;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TypeError;
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
     * Test that a float argument is rejected. The constructor accepts exact integers only; use
     * fromFloat() to convert a float.
     */
    public function testConstructorWithFloatThrows(): void
    {
        $this->expectException(TypeError::class);
        /** @phpstan-ignore-next-line argument.type */
        new Rational(0.5);
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
     * Test PHP_INT_MIN numerator with odd denominator throws DomainException.
     *
     * simplify() can't compute the GCD of PHP_INT_MIN with an odd counterpart. Unlike the old
     * constructor, this no longer falls back to a float approximation — the constructor is
     * int-only and tight, so an unsimplifiable ratio is now a hard error. Use fromFloat() if an
     * approximation is acceptable.
     */
    public function testConstructorWithMinIntNumeratorAndOddDenominatorThrows(): void
    {
        $this->expectException(OverflowException::class);
        new Rational(PHP_INT_MIN, 3);
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
}
