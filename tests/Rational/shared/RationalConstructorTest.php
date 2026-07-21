<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DomainException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Rational::class)]
class RationalConstructorTest extends TestCase
{
    #region Method __construct() tests.

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
        new Rational(0.5); // @phpstan-ignore argument.type
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
     * Test that zero denominator throws ArithmeticException.
     */
    public function testZeroDenominatorThrows(): void
    {
        $this->expectException(ArithmeticException::class);
        new Rational(1, 0);
    }

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
        $this->expectException(DomainException::class);
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
        $this->expectException(DomainException::class);
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
     * Test PHP_INT_MIN numerator with denominator 1 throws DomainException.
     */
    public function testConstructorWithMinIntNumeratorAndDenominator1Throws(): void
    {
        $this->expectException(DomainException::class);
        new Rational(PHP_INT_MIN, 1);
    }

    /**
     * Test PHP_INT_MIN numerator with denominator -1 throws DomainException.
     */
    public function testConstructorWithMinIntNumeratorAndDenominatorNeg1Throws(): void
    {
        $this->expectException(DomainException::class);
        new Rational(PHP_INT_MIN, -1);
    }

    /**
     * Test PHP_INT_MIN numerator with odd denominator throws DomainException.
     *
     * simplify() can't compute the GCD of PHP_INT_MIN with an odd counterpart. This isn't a
     * magnitude problem — PHP_INT_MIN/3 is well within the representable range — it's specifically
     * this exact integer ratio that can't be reduced, since PHP_INT_MIN can't be safely negated.
     * The constructor is int-only and tight, so it doesn't fall back to a float approximation; use
     * fromFloat() if an approximation is acceptable.
     */
    public function testConstructorWithMinIntNumeratorAndOddDenominatorThrows(): void
    {
        $this->expectException(DomainException::class);
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
