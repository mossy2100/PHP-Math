<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use OceanMoon\Math\Complex;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Math\M_I;

#[CoversClass(Complex::class)]
class ComplexBinaryArithmeticTest extends TestCase
{
    use FloatAssertions;

    #region Method add() tests.

    /**
     * Test addition of two complex numbers.
     */
    public function testAddComplex(): void
    {
        $z1 = new Complex(3, 4);
        $z2 = new Complex(1, 2);
        $result = $z1->add($z2);

        $this->assertSame(4.0, $result->real);
        $this->assertSame(6.0, $result->imaginary);
    }

    /**
     * Test addition with real number.
     */
    public function testAddReal(): void
    {
        $z = new Complex(3, 4);
        $result = $z->add(5);

        $this->assertSame(8.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);

        $result2 = $z->add(2.5);
        $this->assertSame(5.5, $result2->real);
        $this->assertSame(4.0, $result2->imaginary);
    }

    /**
     * Test add() does not modify the original (immutability).
     */
    public function testAddDoesNotMutate(): void
    {
        $z = new Complex(3, 4);

        $z->add(new Complex(1, 1));

        $this->assertSame(3.0, $z->real);
        $this->assertSame(4.0, $z->imaginary);
    }

    #endregion

    #region Method sub() tests.

    /**
     * Test subtraction of two complex numbers.
     */
    public function testSubComplex(): void
    {
        $z1 = new Complex(5, 7);
        $z2 = new Complex(2, 3);
        $result = $z1->sub($z2);

        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test subtraction with real number.
     */
    public function testSubReal(): void
    {
        $z = new Complex(5, 4);
        $result = $z->sub(3);

        $this->assertSame(2.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);

        $result2 = $z->sub(1.5);
        $this->assertSame(3.5, $result2->real);
        $this->assertSame(4.0, $result2->imaginary);
    }

    /**
     * Test sub() does not modify the original (immutability).
     */
    public function testSubDoesNotMutate(): void
    {
        $z = new Complex(3, 4);

        $z->sub(new Complex(1, 1));

        $this->assertSame(3.0, $z->real);
        $this->assertSame(4.0, $z->imaginary);
    }

    #endregion

    #region Method mul() tests.

    /**
     * Test multiplication of two complex numbers.
     */
    public function testMulComplex(): void
    {
        // (3 + 4i)(1 + 2i) = 3 + 6i + 4i + 8i² = 3 + 10i - 8 = -5 + 10i
        $z1 = new Complex(3, 4);
        $z2 = new Complex(1, 2);
        $result = $z1->mul($z2);

        $this->assertSame(-5.0, $result->real);
        $this->assertSame(10.0, $result->imaginary);
    }

    /**
     * Test multiplication with real number.
     */
    public function testMulReal(): void
    {
        $z = new Complex(3, 4);
        $result = $z->mul(2);

        $this->assertSame(6.0, $result->real);
        $this->assertSame(8.0, $result->imaginary);

        $result2 = $z->mul(0.5);
        $this->assertSame(1.5, $result2->real);
        $this->assertSame(2.0, $result2->imaginary);
    }

    /**
     * Test multiplication by i gives correct result.
     */
    public function testMulByI(): void
    {
        // (3 + 4i) * i = 3i + 4i² = 3i - 4 = -4 + 3i
        $z = new Complex(3, 4);
        $result = $z->mul(M_I);

        $this->assertSame(-4.0, $result->real);
        $this->assertSame(3.0, $result->imaginary);
    }

    /**
     * Test mul() does not modify the original (immutability).
     */
    public function testMulDoesNotMutate(): void
    {
        $z = new Complex(3, 4);

        $z->mul(2);

        $this->assertSame(3.0, $z->real);
        $this->assertSame(4.0, $z->imaginary);
    }

    #endregion

    #region Method div() tests.

    /**
     * Test division of two complex numbers.
     */
    public function testDivComplex(): void
    {
        // (3 + 4i) / (1 + 2i) = (3 + 4i)(1 - 2i) / 5 = (3 - 6i + 4i - 8i²) / 5 = (11 - 2i) / 5
        $z1 = new Complex(3, 4);
        $z2 = new Complex(1, 2);
        $result = $z1->div($z2);

        $this->assertEqualsWithDelta(2.2, $result->real, EPSILON);
        $this->assertEqualsWithDelta(-0.4, $result->imaginary, EPSILON);
    }

    /**
     * Test division by real number.
     */
    public function testDivReal(): void
    {
        $z = new Complex(6, 8);
        $result = $z->div(2);

        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);

        $result2 = $z->div(4.0);
        $this->assertSame(1.5, $result2->real);
        $this->assertSame(2.0, $result2->imaginary);
    }

    /**
     * Test division by zero throws exception.
     */
    public function testDivByZero(): void
    {
        $z = new Complex(3, 4);

        $this->expectException(ArithmeticException::class);
        $z->div(0);
    }

    /**
     * Test division by complex zero throws exception.
     */
    public function testDivByComplexZero(): void
    {
        $z = new Complex(3, 4);

        $this->expectException(ArithmeticException::class);
        $z->div(new Complex(0, 0));
    }

    /**
     * Data for divisors whose components are too small or too large to square, but whose quotient is representable.
     *
     * @return array<string, array{float, float, float, float, float, float}> Dividend real and imaginary parts, divisor
     * real and imaginary parts, expected real and imaginary parts.
     */
    public static function divExtremeProvider(): array
    {
        return [
            'tiny real divisor'           => [1.0, 0.0, 1e-160, 0.0, 1e160, 0.0],
            'tiny complex divisor'        => [1.0, 0.0, 1e-300, 1e-300, 5e299, -5e299],
            'tiny imaginary divisor'      => [1.0, 0.0, 0.0, 1e-300, 0.0, -1e300],
            'huge real divisor'           => [1.0, 0.0, 1e200, 0.0, 1e-200, 0.0],
            'huge complex divisor'        => [1e200, 0.0, 1e200, 1e200, 0.5, -0.5],
            'huge dividend and divisor'   => [3e300, 4e300, 1e300, 2e300, 2.2, -0.4],
            'tiny dividend, tiny divisor' => [1e-300, 0.0, 0.0, 2e-300, 0.0, -0.5],
        ];
    }

    /**
     * Test div() gives an accurate result for a divisor whose components would underflow or overflow if squared.
     */
    #[DataProvider('divExtremeProvider')]
    public function testDivExtremeDivisor(float $a, float $b, float $c, float $d, float $real, float $imag): void
    {
        $result = new Complex($a, $b)->div(new Complex($c, $d));

        // Use a zero absolute tolerance, so a result that wrongly underflows to 0 fails.
        $this->assertApproxEqual($real, $result->real, absTol: 0.0);
        $this->assertApproxEqual($imag, $result->imaginary, absTol: 0.0);
    }

    /**
     * Test div() throws when the quotient is too large to represent as a float.
     */
    public function testDivOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot divide 1 by 9.9998886718268E-321. The result would overflow.');
        new Complex(1)->div(new Complex(1e-320));
    }

    /**
     * Test div() does not modify the original (immutability).
     */
    public function testDivDoesNotMutate(): void
    {
        $z = new Complex(3, 4);

        $z->div(2);

        $this->assertSame(3.0, $z->real);
        $this->assertSame(4.0, $z->imaginary);
    }

    #endregion
}
