<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexUnaryArithmeticTest extends TestCase
{
    #region Method neg() tests.

    /**
     * Test negation of complex numbers.
     */
    public function testNeg(): void
    {
        $z1 = new Complex(3, 4);
        $result = $z1->neg();

        $this->assertSame(-3.0, $result->real);
        $this->assertSame(-4.0, $result->imaginary);

        // Test negation of negative numbers
        $z2 = new Complex(-5, -2);
        $result2 = $z2->neg();

        $this->assertSame(5.0, $result2->real);
        $this->assertSame(2.0, $result2->imaginary);

        // Test negation of zero
        $z3 = new Complex(0, 0);
        $result3 = $z3->neg();

        $this->assertSame(0.0, $result3->real);
        $this->assertSame(0.0, $result3->imaginary);
    }

    /**
     * Test neg() does not modify the original (immutability).
     */
    public function testNegDoesNotMutate(): void
    {
        $z = new Complex(3, 4);

        $z->neg();

        $this->assertSame(3.0, $z->real);
        $this->assertSame(4.0, $z->imaginary);
    }

    #endregion

    #region Method inv() tests.

    /**
     * Test reciprocal (multiplicative inverse).
     */
    public function testInv(): void
    {
        // 1 / (3 + 4i) = (3 - 4i) / 25 = 0.12 - 0.16i
        $z = new Complex(3, 4);
        $result = $z->inv();

        $this->assertEqualsWithDelta(0.12, $result->real, EPSILON);
        $this->assertEqualsWithDelta(-0.16, $result->imaginary, EPSILON);

        // Verify z * inv(z) = 1
        $product = $z->mul($result);
        $this->assertEqualsWithDelta(1.0, $product->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $product->imaginary, EPSILON);
    }

    /**
     * Test inv() does not modify the original (immutability).
     */
    public function testInvDoesNotMutate(): void
    {
        $z = new Complex(3, 4);

        $z->inv();

        $this->assertSame(3.0, $z->real);
        $this->assertSame(4.0, $z->imaginary);
    }

    #endregion

    #region Method conj() tests.

    /**
     * Test conjugate.
     */
    public function testConj(): void
    {
        $z = new Complex(3, 4);
        $result = $z->conj();

        $this->assertSame(3.0, $result->real);
        $this->assertSame(-4.0, $result->imaginary);

        // Test conjugate of conjugate
        $result2 = $result->conj();
        $this->assertSame(3.0, $result2->real);
        $this->assertSame(4.0, $result2->imaginary);

        // Test conjugate of real number
        $z2 = new Complex(5, 0);
        $result3 = $z2->conj();
        $this->assertSame(5.0, $result3->real);
        $this->assertSame(0.0, $result3->imaginary);
    }

    /**
     * Test conj() does not modify the original (immutability).
     */
    public function testConjDoesNotMutate(): void
    {
        $z = new Complex(3, 4);

        $z->conj();

        $this->assertSame(3.0, $z->real);
        $this->assertSame(4.0, $z->imaginary);
    }

    #endregion
}
