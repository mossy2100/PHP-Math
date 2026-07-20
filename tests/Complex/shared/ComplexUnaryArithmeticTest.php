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

    #region Conjugate identity tests.

    /**
     * Test conj(conj(z)) = z (involution).
     */
    public function testConjInvolution(): void
    {
        $z = new Complex(3, 4);
        $result = $z->conj()->conj();

        $this->assertSame($z->real, $result->real);
        $this->assertSame($z->imaginary, $result->imaginary);
    }

    /**
     * Test z + conj(z) = 2*Re(z), i.e. the result is real.
     */
    public function testConjSumIsTwiceRealPart(): void
    {
        $z = new Complex(3, 4);
        $sum = $z->add($z->conj());

        $this->assertEqualsWithDelta(2 * $z->real, $sum->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $sum->imaginary, EPSILON);
    }

    /**
     * Test z - conj(z) = 2i*Im(z), i.e. the result is purely imaginary.
     */
    public function testConjDifferenceIsTwiceImaginaryPart(): void
    {
        $z = new Complex(3, 4);
        $diff = $z->sub($z->conj());

        $this->assertEqualsWithDelta(0.0, $diff->real, EPSILON);
        $this->assertEqualsWithDelta(2 * $z->imaginary, $diff->imaginary, EPSILON);
    }

    /**
     * Test z * conj(z) = |z|², i.e. the result is real and equal to the squared magnitude.
     */
    public function testConjProductIsMagnitudeSquared(): void
    {
        $z = new Complex(3, 4);
        $product = $z->mul($z->conj());

        $this->assertEqualsWithDelta($z->magnitude ** 2, $product->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $product->imaginary, EPSILON);
    }

    /**
     * Test |z| = sqrt(z * conj(z)).
     */
    public function testMagnitudeEqualsSqrtOfConjProduct(): void
    {
        $z = new Complex(3, 4);
        $result = $z->mul($z->conj())->sqrt();

        $this->assertEqualsWithDelta($z->magnitude, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);
    }

    /**
     * Test conj(z1 + z2) = conj(z1) + conj(z2).
     */
    public function testConjOfSumEqualsSumOfConjs(): void
    {
        $z1 = new Complex(3, 4);
        $z2 = new Complex(-2, 5);

        $lhs = $z1->add($z2)->conj();
        $rhs = $z1->conj()->add($z2->conj());

        $this->assertEqualsWithDelta($rhs->real, $lhs->real, EPSILON);
        $this->assertEqualsWithDelta($rhs->imaginary, $lhs->imaginary, EPSILON);
    }

    /**
     * Test conj(z1 * z2) = conj(z1) * conj(z2).
     */
    public function testConjOfProductEqualsProductOfConjs(): void
    {
        $z1 = new Complex(3, 4);
        $z2 = new Complex(-2, 5);

        $lhs = $z1->mul($z2)->conj();
        $rhs = $z1->conj()->mul($z2->conj());

        $this->assertEqualsWithDelta($rhs->real, $lhs->real, EPSILON);
        $this->assertEqualsWithDelta($rhs->imaginary, $lhs->imaginary, EPSILON);
    }

    /**
     * Test conj(z1 / z2) = conj(z1) / conj(z2).
     */
    public function testConjOfQuotientEqualsQuotientOfConjs(): void
    {
        $z1 = new Complex(3, 4);
        $z2 = new Complex(-2, 5);

        $lhs = $z1->div($z2)->conj();
        $rhs = $z1->conj()->div($z2->conj());

        $this->assertEqualsWithDelta($rhs->real, $lhs->real, EPSILON);
        $this->assertEqualsWithDelta($rhs->imaginary, $lhs->imaginary, EPSILON);
    }

    /**
     * Test conj(z^w) = conj(z)^conj(w).
     */
    public function testConjOfPowEqualsPowOfConjs(): void
    {
        $z = new Complex(3, 4);
        $w = new Complex(2, 1);

        $lhs = $z->pow($w)->conj();
        $rhs = $z->conj()->pow($w->conj());

        $this->assertEqualsWithDelta($rhs->real, $lhs->real, EPSILON);
        $this->assertEqualsWithDelta($rhs->imaginary, $lhs->imaginary, EPSILON);
    }

    /**
     * Test conj(exp(z)) = exp(conj(z)).
     */
    public function testConjOfExpEqualsExpOfConj(): void
    {
        $z = new Complex(3, 4);

        $lhs = $z->exp()->conj();
        $rhs = $z->conj()->exp();

        $this->assertEqualsWithDelta($rhs->real, $lhs->real, EPSILON);
        $this->assertEqualsWithDelta($rhs->imaginary, $lhs->imaginary, EPSILON);
    }

    /**
     * Test conj(ln(z)) = ln(conj(z)).
     */
    public function testConjOfLnEqualsLnOfConj(): void
    {
        $z = new Complex(3, 4);

        $lhs = $z->ln()->conj();
        $rhs = $z->conj()->ln();

        $this->assertEqualsWithDelta($rhs->real, $lhs->real, EPSILON);
        $this->assertEqualsWithDelta($rhs->imaginary, $lhs->imaginary, EPSILON);
    }

    /**
     * Test |conj(z)| = |z|, i.e. conjugation preserves magnitude.
     */
    public function testConjPreservesMagnitude(): void
    {
        $z = new Complex(3, 4);

        $this->assertEqualsWithDelta($z->magnitude, $z->conj()->magnitude, EPSILON);
    }

    /**
     * Test arg(conj(z)) = -arg(z), i.e. conjugation negates the phase.
     */
    public function testConjNegatesPhase(): void
    {
        $z = new Complex(3, 4);

        $this->assertEqualsWithDelta(-$z->phase, $z->conj()->phase, EPSILON);
    }

    /**
     * Test conj(z) = z if and only if z is real.
     */
    public function testConjEqualsOriginalOnlyForRealNumbers(): void
    {
        $real = new Complex(5, 0);
        $this->assertTrue($real->equal($real->conj()));

        $complex = new Complex(5, 1);
        $this->assertFalse($complex->equal($complex->conj()));
    }

    #endregion
}
