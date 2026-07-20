<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexHyperbolicTest extends TestCase
{
    #region Method sinh() tests.

    /**
     * Test sinh (hyperbolic sine).
     */
    public function testSinh(): void
    {
        // sinh(0) = 0
        $result = new Complex(0)->sinh();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // sinh(1) ≈ 1.1752
        $result2 = new Complex(1)->sinh();
        $this->assertEqualsWithDelta(sinh(1), $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test sinh(z) = sinh(x)cos(y) + i·cosh(x)sin(y) identity.
     */
    public function testSinhIdentity(): void
    {
        $z = new Complex(1, 1);
        $result = $z->sinh();

        $expectedReal = sinh(1) * cos(1);
        $expectedImag = cosh(1) * sin(1);

        $this->assertEqualsWithDelta($expectedReal, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $result->imaginary, EPSILON);
    }

    #endregion

    #region Method cosh() tests.

    /**
     * Test cosh (hyperbolic cosine).
     */
    public function testCosh(): void
    {
        // cosh(0) = 1
        $result = new Complex(0)->cosh();
        $this->assertEqualsWithDelta(1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // cosh(1) ≈ 1.5431
        $result2 = new Complex(1)->cosh();
        $this->assertEqualsWithDelta(cosh(1), $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test cosh(z) = cosh(x)cos(y) + i·sinh(x)sin(y) identity.
     */
    public function testCoshIdentity(): void
    {
        $z = new Complex(1, 1);
        $result = $z->cosh();

        $expectedReal = cosh(1) * cos(1);
        $expectedImag = sinh(1) * sin(1);

        $this->assertEqualsWithDelta($expectedReal, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $result->imaginary, EPSILON);
    }

    #endregion

    #region Method tanh() tests.

    /**
     * Test tanh (hyperbolic tangent).
     */
    public function testTanh(): void
    {
        // tanh(0) = 0
        $result = new Complex(0)->tanh();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // tanh(1) ≈ 0.7616
        $result2 = new Complex(1)->tanh();
        $this->assertEqualsWithDelta(tanh(1), $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test tanh(z) = sinh(z)/cosh(z) identity.
     */
    public function testTanhIdentity(): void
    {
        $z = new Complex(1, 1);

        $tanh = $z->tanh();
        $ratio = $z->sinh()->div($z->cosh());

        $this->assertEqualsWithDelta($ratio->real, $tanh->real, EPSILON);
        $this->assertEqualsWithDelta($ratio->imaginary, $tanh->imaginary, EPSILON);
    }

    #endregion

    #region Hyperbolic Pythagorean identity tests.

    /**
     * Test hyperbolic Pythagorean identity: cosh²(z) - sinh²(z) = 1.
     */
    public function testHyperbolicPythagoreanIdentity(): void
    {
        $z = new Complex(1, 1);

        $cosh2 = $z->cosh()->sqr();
        $sinh2 = $z->sinh()->sqr();
        $result = $cosh2->sub($sinh2);

        $this->assertEqualsWithDelta(1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);
    }

    #endregion
}
