<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Math\M_I;

#[CoversClass(Complex::class)]
class ComplexBinaryArithmeticTest extends TestCase
{
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
