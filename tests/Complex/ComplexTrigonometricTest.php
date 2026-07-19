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

    /**
     * Test sec (secant).
     */
    public function testSec(): void
    {
        // sec(0) = 1
        $result = new Complex(0)->sec();
        $this->assertEqualsWithDelta(1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // sec(π/3) = 2
        $result2 = new Complex(M_PI / 3)->sec();
        $this->assertEqualsWithDelta(2.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test sec(z) = 1/cos(z) identity.
     */
    public function testSecIdentity(): void
    {
        $z = new Complex(1, 1);

        $sec = $z->sec();
        $invCos = $z->cos()->inv();

        $this->assertEqualsWithDelta($invCos->real, $sec->real, EPSILON);
        $this->assertEqualsWithDelta($invCos->imaginary, $sec->imaginary, EPSILON);
    }

    /**
     * Test csc (cosecant).
     */
    public function testCsc(): void
    {
        // csc(π/2) = 1
        $result = new Complex(M_PI / 2)->csc();
        $this->assertEqualsWithDelta(1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // csc(π/6) = 2
        $result2 = new Complex(M_PI / 6)->csc();
        $this->assertEqualsWithDelta(2.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test csc(z) = 1/sin(z) identity.
     */
    public function testCscIdentity(): void
    {
        $z = new Complex(1, 1);

        $csc = $z->csc();
        $invSin = $z->sin()->inv();

        $this->assertEqualsWithDelta($invSin->real, $csc->real, EPSILON);
        $this->assertEqualsWithDelta($invSin->imaginary, $csc->imaginary, EPSILON);
    }

    /**
     * Test cot (cotangent).
     */
    public function testCot(): void
    {
        // cot(π/4) = 1
        $result = new Complex(M_PI / 4)->cot();
        $this->assertEqualsWithDelta(1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // cot(π/6) = √3
        $result2 = new Complex(M_PI / 6)->cot();
        $this->assertEqualsWithDelta(sqrt(3), $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test cot(z) = 1/tan(z) identity.
     */
    public function testCotIdentity(): void
    {
        $z = new Complex(1, 1);

        $cot = $z->cot();
        $invTan = $z->tan()->inv();

        $this->assertEqualsWithDelta($invTan->real, $cot->real, EPSILON);
        $this->assertEqualsWithDelta($invTan->imaginary, $cot->imaginary, EPSILON);
    }

    /**
     * Test asec (inverse secant).
     */
    public function testAsec(): void
    {
        // asec(1) = 0
        $result = new Complex(1)->asec();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // asec(2) = π/3
        $result2 = new Complex(2)->asec();
        $this->assertEqualsWithDelta(M_PI / 3, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test asec(sec(z)) = z for principal values.
     */
    public function testAsecSecIdentity(): void
    {
        $z = new Complex(0.5, 0.3);

        $sec = $z->sec();
        $asec = $sec->asec();

        $this->assertEqualsWithDelta($z->real, $asec->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $asec->imaginary, EPSILON);
    }

    /**
     * Test acsc (inverse cosecant).
     */
    public function testAcsc(): void
    {
        // acsc(1) = π/2
        $result = new Complex(1)->acsc();
        $this->assertEqualsWithDelta(M_PI / 2, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // acsc(2) = π/6
        $result2 = new Complex(2)->acsc();
        $this->assertEqualsWithDelta(M_PI / 6, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test acsc(csc(z)) = z for principal values.
     */
    public function testAcscCscIdentity(): void
    {
        $z = new Complex(0.5, 0.3);

        $csc = $z->csc();
        $acsc = $csc->acsc();

        $this->assertEqualsWithDelta($z->real, $acsc->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $acsc->imaginary, EPSILON);
    }

    /**
     * Test acot (inverse cotangent).
     */
    public function testAcot(): void
    {
        // acot(1) = π/4
        $result = new Complex(1)->acot();
        $this->assertEqualsWithDelta(M_PI / 4, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // acot(√3) = π/6
        $result2 = new Complex(sqrt(3))->acot();
        $this->assertEqualsWithDelta(M_PI / 6, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);
    }

    /**
     * Test acot(cot(z)) = z for principal values.
     */
    public function testAcotCotIdentity(): void
    {
        $z = new Complex(0.5, 0.3);

        $cot = $z->cot();
        $acot = $cot->acot();

        $this->assertEqualsWithDelta($z->real, $acot->real, EPSILON);
        $this->assertEqualsWithDelta($z->imaginary, $acot->imaginary, EPSILON);
    }

    /**
     * Test reciprocal identities for complex numbers.
     */
    public function testReciprocalIdentitiesComplex(): void
    {
        $z = new Complex(2, 3);

        // sec(z) * cos(z) = 1
        $product1 = $z->sec()->mul($z->cos());
        $this->assertEqualsWithDelta(1.0, $product1->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $product1->imaginary, EPSILON);

        // csc(z) * sin(z) = 1
        $product2 = $z->csc()->mul($z->sin());
        $this->assertEqualsWithDelta(1.0, $product2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $product2->imaginary, EPSILON);

        // cot(z) * tan(z) = 1
        $product3 = $z->cot()->mul($z->tan());
        $this->assertEqualsWithDelta(1.0, $product3->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $product3->imaginary, EPSILON);
    }

    #endregion
}
