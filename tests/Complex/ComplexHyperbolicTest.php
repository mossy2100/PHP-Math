<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Complex hyperbolic and inverse hyperbolic functions.
 */
#[CoversClass(Complex::class)]
class ComplexHyperbolicTest extends TestCase
{
    #region Hyperbolic functions

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

    #region Inverse hyperbolic functions

    /**
     * Test asinh (inverse hyperbolic sine).
     */
    public function testAsinh(): void
    {
        // asinh(0) = 0
        $result = new Complex(0)->asinh();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // asinh(1) ≈ 0.8814
        $result2 = new Complex(1)->asinh();
        $this->assertEqualsWithDelta(asinh(1), $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test asinh(sinh(z)) = z round-trip identity.
     */
    public function testAsinhSinhIdentity(): void
    {
        $z = new Complex(0.5, 0.5);

        $result = $z->sinh()->asinh();

        $this->assertEqualsWithDelta($z->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $result->imaginary, EPSILON);
    }

    /**
     * Test acosh (inverse hyperbolic cosine).
     */
    public function testAcosh(): void
    {
        // acosh(1) = 0
        $result = new Complex(1)->acosh();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // acosh(2) ≈ 1.3170
        $result2 = new Complex(2)->acosh();
        $this->assertEqualsWithDelta(acosh(2), $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test acosh(cosh(z)) = z round-trip identity.
     */
    public function testAcoshCoshIdentity(): void
    {
        $z = new Complex(0.5, 0.5);

        $result = $z->cosh()->acosh();

        $this->assertEqualsWithDelta($z->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $result->imaginary, EPSILON);
    }

    /**
     * Test atanh (inverse hyperbolic tangent).
     */
    public function testAtanh(): void
    {
        // atanh(0) = 0
        $result = new Complex(0)->atanh();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // atanh(0.5) ≈ 0.5493
        $result2 = new Complex(0.5)->atanh();
        $this->assertEqualsWithDelta(atanh(0.5), $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test atanh(tanh(z)) = z round-trip identity.
     */
    public function testAtanhTanhIdentity(): void
    {
        $z = new Complex(0.5, 0.5);

        $result = $z->tanh()->atanh();

        $this->assertEqualsWithDelta($z->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $result->imaginary, EPSILON);
    }

    #endregion
}
