<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DomainException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Rational;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalPowerTest extends TestCase
{
    #region Method pow() tests.

    /**
     * Test power with positive exponent.
     */
    public function testPowPositive(): void
    {
        // (2/3)^2 = 4/9
        $r1 = new Rational(2, 3);
        $result = $r1->pow(2);

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
        $r1 = new Rational(3, 4);
        $result = $r1->pow(0);

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
        $r1 = new Rational(3, 4);
        $result = $r1->pow(-1);

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
        $r1 = new Rational(1);
        $result = $r1->pow(PHP_INT_MIN);

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
        $this->expectException(ArithmeticException::class);
        $r = new Rational(0);
        $r->pow(-1);
    }

    /**
     * Test power throws when the result overflows an int.
     */
    public function testPowOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot raise the given Rational to the given power. The result would overflow.');
        $r = new Rational(3);
        $r->pow(50);
    }

    /**
     * Test pow() does not modify the original (immutability).
     */
    public function testPowDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->pow(2);

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    /**
     * Data for Rational exponents with an exact result.
     *
     * @return array<string, array{int, int, int, int, int, int}> Base numerator and denominator, exponent numerator
     * and denominator, expected numerator and denominator.
     */
    public static function powRationalProvider(): array
    {
        return [
            'square root'           => [4, 9, 1, 2, 2, 3],
            'negative cube root'    => [8, 27, -1, 3, 3, 2],
            'root then power'       => [8, 27, -2, 3, 9, 4],
            'odd root of negative'  => [-8, 27, 1, 3, -2, 3],
            'odd root, even power'  => [-8, 27, 2, 3, 4, 9],
            'zero base'             => [0, 1, 1, 2, 0, 1],
            'base one'              => [1, 1, 5, 7, 1, 1],
            'whole-number exponent' => [2, 3, 3, 1, 8, 27],
            'integer base'          => [4, 1, -1, 2, 1, 2],
            'largest square root'   => [
                3037000499 * 3037000499,
                1,
                1,
                2,
                3037000499,
                1,
            ],
        ];
    }

    /**
     * Test pow() with a Rational exponent returns the exact result.
     */
    #[DataProvider('powRationalProvider')]
    public function testPowRationalExponent(int $a, int $b, int $p, int $q, int $num, int $den): void
    {
        $result = new Rational($a, $b)->pow(new Rational($p, $q));

        $this->assertSame($num, $result->numerator);
        $this->assertSame($den, $result->denominator);
    }

    /**
     * Data for Rational exponents whose result is irrational.
     *
     * @return array<string, array{int, int, int, int}> Base numerator and denominator, exponent numerator and
     * denominator.
     */
    public static function powRationalIrrationalProvider(): array
    {
        return [
            'numerator has no root'   => [2, 9, 1, 2],
            'denominator has no root' => [4, 3, 1, 2],
            'odd root of negative'    => [-2, 1, 1, 3],
            'root degree 63 or more'  => [2, 1, 1, 64],
            'PHP_INT_MAX square root' => [PHP_INT_MAX, 1, 1, 2],
        ];
    }

    /**
     * Test pow() with a Rational exponent throws when the result is irrational.
     */
    #[DataProvider('powRationalIrrationalProvider')]
    public function testPowRationalExponentIrrationalThrows(int $a, int $b, int $p, int $q): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('irrational');
        new Rational($a, $b)->pow(new Rational($p, $q));
    }

    /**
     * Test pow() with an even-root Rational exponent throws for a negative base.
     */
    public function testPowRationalExponentEvenRootOfNegativeThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot raise -4/9 to power 1/2. The result is not real.');
        new Rational(-4, 9)->pow(new Rational(1, 2));
    }

    /**
     * Test pow() with a negative Rational exponent throws for a zero base.
     */
    public function testPowRationalExponentZeroBaseNegativeThrows(): void
    {
        $this->expectException(ArithmeticException::class);
        new Rational(0)->pow(new Rational(-1, 2));
    }

    /**
     * Test pow() with a Rational exponent throws when the exact result overflows.
     */
    public function testPowRationalExponentOverflowThrows(): void
    {
        // 4^(127/2) = 2^127, which doesn't fit in an int.
        $this->expectException(OverflowException::class);
        new Rational(4)->pow(new Rational(127, 2));
    }

    #endregion

    #region Method sqr() tests.

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
     * Test sqr() throws on overflow, with a message naming sqr() rather than mul().
     */
    public function testSqrOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot square the given Rational. The result would overflow.');
        $r = new Rational(PHP_INT_MAX);
        $r->sqr();
    }

    /**
     * Test sqr() does not modify the original (immutability).
     */
    public function testSqrDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->sqr();

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    #endregion
}
