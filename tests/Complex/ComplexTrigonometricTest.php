<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexTrigonometricTest extends TestCase
{
    #region Trigonometric methods

    /**
     * Test sin of real numbers.
     */
    public function testSinReal(): void
    {
        // sin(0) = 0
        $result = new Complex(0)->sin();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // sin(π/2) = 1
        $result2 = new Complex(M_PI / 2)->sin();
        $this->assertEqualsWithDelta(1.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);

        // sin(π) = 0
        $result3 = new Complex(M_PI)->sin();
        $this->assertEqualsWithDelta(0.0, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result3->imaginary, EPSILON);
    }

    /**
     * Test sin of complex numbers.
     */
    public function testSinComplex(): void
    {
        $z = new Complex(1, 1);
        $result = $z->sin();

        // sin(x+iy) = sin(x)cosh(y) + i*cos(x)sinh(y)
        $expectedReal = sin(1) * cosh(1);
        $expectedImag = cos(1) * sinh(1);

        $this->assertEqualsWithDelta($expectedReal, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $result->imaginary, EPSILON);
    }

    /**
     * Test cos of real numbers.
     */
    public function testCosReal(): void
    {
        // cos(0) = 1
        $result = new Complex(0)->cos();
        $this->assertEqualsWithDelta(1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // cos(π/2) = 0
        $result2 = new Complex(M_PI / 2)->cos();
        $this->assertEqualsWithDelta(0.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);

        // cos(π) = -1
        $result3 = new Complex(M_PI)->cos();
        $this->assertEqualsWithDelta(-1.0, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result3->imaginary, EPSILON);
    }

    /**
     * Test cos of complex numbers.
     */
    public function testCosComplex(): void
    {
        $z = new Complex(1, 1);
        $result = $z->cos();

        // cos(x+iy) = cos(x)cosh(y) - i*sin(x)sinh(y)
        $expectedReal = cos(1) * cosh(1);
        $expectedImag = -sin(1) * sinh(1);

        $this->assertEqualsWithDelta($expectedReal, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $result->imaginary, EPSILON);
    }

    /**
     * Test tan of real numbers.
     */
    public function testTanReal(): void
    {
        // tan(0) = 0
        $result = new Complex(0)->tan();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // tan(π/4) = 1
        $result2 = new Complex(M_PI / 4)->tan();
        $this->assertEqualsWithDelta(1.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test tan of complex numbers.
     */
    public function testTanComplex(): void
    {
        $z = new Complex(1, 1);
        $result = $z->tan();

        // tan(z) = sin(z) / cos(z)
        $sin = $z->sin();
        $cos = $z->cos();
        $expected = $sin->div($cos);

        $this->assertEqualsWithDelta($expected->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, EPSILON);
    }

    /**
     * Test sin²(z) + cos²(z) = 1 (Pythagorean identity).
     */
    public function testPythagoreanIdentity(): void
    {
        $z = new Complex(2, 3);

        $sin = $z->sin();
        $cos = $z->cos();

        $sin2 = $sin->sqr();
        $cos2 = $cos->sqr();

        $sum = $sin2->add($cos2);

        $this->assertEqualsWithDelta(1.0, $sum->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $sum->imaginary, EPSILON);
    }

    #endregion

    #region Inverse trigonometric methods

    /**
     * Test asin (inverse sine).
     */
    public function testAsin(): void
    {
        // asin(0) = 0
        $result = new Complex(0)->asin();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // asin(1) = π/2
        $result2 = new Complex(1)->asin();
        $this->assertEqualsWithDelta(M_PI / 2, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test asin(sin(z)) = z for principal values.
     */
    public function testAsinSinIdentity(): void
    {
        $z = new Complex(0.5, 0.3);

        $sin = $z->sin();
        $asin = $sin->asin();

        $this->assertEqualsWithDelta($z->real, $asin->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $asin->imaginary, EPSILON);
    }

    /**
     * Test acos (inverse cosine).
     */
    public function testAcos(): void
    {
        // acos(1) = 0
        $result = new Complex(1)->acos();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // acos(0) = π/2
        $result2 = new Complex(0)->acos();
        $this->assertEqualsWithDelta(M_PI / 2, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);

        // acos(-1) = π
        $result3 = new Complex(-1)->acos();
        $this->assertEqualsWithDelta(M_PI, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result3->imaginary, EPSILON);
    }

    /**
     * Test acos(cos(z)) = z for principal values.
     */
    public function testAcosCosIdentity(): void
    {
        $z = new Complex(0.5, 0.3);

        $cos = $z->cos();
        $acos = $cos->acos();

        $this->assertEqualsWithDelta($z->real, $acos->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $acos->imaginary, EPSILON);
    }

    /**
     * Test atan (inverse tangent).
     */
    public function testAtan(): void
    {
        // atan(0) = 0
        $result = new Complex(0)->atan();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // atan(1) = π/4
        $result2 = new Complex(1)->atan();
        $this->assertEqualsWithDelta(M_PI / 4, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test atan(tan(z)) = z for principal values.
     */
    public function testAtanTanIdentity(): void
    {
        $z = new Complex(0.5, 0.3);

        $tan = $z->tan();
        $atan = $tan->atan();

        $this->assertEqualsWithDelta($z->real, $atan->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $atan->imaginary, EPSILON);
    }

    #endregion
}
