<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexInverseTrigonometricTest extends TestCase
{
    #region Method asin() tests.

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

    #endregion

    #region Method acos() tests.

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

    #endregion

    #region Method atan() tests.

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
