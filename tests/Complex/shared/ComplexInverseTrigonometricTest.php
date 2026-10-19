<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexInverseTrigonometricTest extends TestCase
{
    use FloatAssertions;

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

    /**
     * Data provider for testAsinReferenceValues(). The expected values are from Python's cmath.asin().
     *
     * @return array<string, array{float, float, float, float}>
     */
    public static function asinReferenceProvider(): array
    {
        return [
            'first quadrant'          => [1.5, 2.5, 0.5155484357349067, 1.777662377506645],
            'second quadrant'         => [-1.5, 2.5, -0.5155484357349067, 1.777662377506645],
            'third quadrant'          => [-1.5, -2.5, -0.5155484357349067, -1.777662377506645],
            'fourth quadrant'         => [1.5, -2.5, 0.5155484357349067, -1.777662377506645],
            'tiny real'               => [1e-20, 0.0, 1e-20, 0.0],
            'tiny imaginary'          => [0.0, 1e-20, 0.0, 1e-20],
            'large negative real'     => [-1e10, 0.0, -1.5707963267948966, 23.7189981105004],
            'large imaginary'         => [0.0, 1e10, 0.0, 23.7189981105004],
            'huge'                    => [1e200, 1e200, 0.7853981633974483, 461.55673936964905],
            'huge negative'           => [-1e300, -1e-300, -1.5707963267948966, -691.4686750787736],
            'real cut, upper side'    => [2.0, 0.0, 1.5707963267948966, 1.3169578969248166],
            'real cut, negative zero' => [2.0, -0.0, 1.5707963267948966, 1.3169578969248166],
        ];
    }

    /**
     * Test asin() against reference values: in every quadrant, at extreme magnitudes, and on each side of its branch
     * cut (the real axis outside [-1, 1]), where the sign of a zero part chooses the side.
     */
    #[DataProvider('asinReferenceProvider')]
    public function testAsinReferenceValues(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $result = new Complex($real, $imag)->asin();

        $this->assertApproxEqual($expectedReal, $result->real, 1e-14, 0.0);
        $this->assertApproxEqual($expectedImag, $result->imaginary, 1e-14, 0.0);
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

    /**
     * Data provider for testAcosReferenceValues(). The expected values are from Python's cmath.acos().
     *
     * @return array<string, array{float, float, float, float}>
     */
    public static function acosReferenceProvider(): array
    {
        return [
            'first quadrant'          => [1.5, 2.5, 1.0552478910599898, -1.777662377506645],
            'second quadrant'         => [-1.5, 2.5, 2.0863447625298033, -1.777662377506645],
            'third quadrant'          => [-1.5, -2.5, 2.0863447625298033, 1.777662377506645],
            'fourth quadrant'         => [1.5, -2.5, 1.0552478910599898, 1.777662377506645],
            'tiny real'               => [1e-20, 0.0, 1.5707963267948966, 0.0],
            'tiny imaginary'          => [0.0, 1e-20, 1.5707963267948966, -1e-20],
            'large negative real'     => [-1e10, 0.0, 3.141592653589793, -23.7189981105004],
            'large imaginary'         => [0.0, 1e10, 1.5707963267948966, -23.7189981105004],
            'huge'                    => [1e200, 1e200, 0.7853981633974484, -461.55673936964905],
            'huge negative'           => [-1e300, -1e-300, 3.141592653589793, 691.4686750787736],
            'real cut, upper side'    => [2.0, 0.0, 0.0, -1.3169578969248166],
            'real cut, negative zero' => [2.0, -0.0, 0.0, -1.3169578969248166],
        ];
    }

    /**
     * Test acos() against reference values: in every quadrant, at extreme magnitudes, and on each side of its branch
     * cut (the real axis outside [-1, 1]), where the sign of a zero part chooses the side.
     */
    #[DataProvider('acosReferenceProvider')]
    public function testAcosReferenceValues(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $result = new Complex($real, $imag)->acos();

        $this->assertApproxEqual($expectedReal, $result->real, 1e-14, 0.0);
        $this->assertApproxEqual($expectedImag, $result->imaginary, 1e-14, 0.0);
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

    /**
     * Data provider for testAtanReferenceValues(). The expected values are from Python's cmath.atan().
     *
     * @return array<string, array{float, float, float, float}>
     */
    public static function atanReferenceProvider(): array
    {
        return [
            'first quadrant'               => [1.5, 2.5, 1.380543138238714, 0.29251781316256364],
            'second quadrant'              => [-1.5, 2.5, -1.380543138238714, 0.29251781316256364],
            'third quadrant'               => [-1.5, -2.5, -1.380543138238714, -0.29251781316256364],
            'fourth quadrant'              => [1.5, -2.5, 1.380543138238714, -0.29251781316256364],
            'tiny real'                    => [1e-20, 0.0, 1e-20, 0.0],
            'tiny imaginary'               => [0.0, 1e-20, 0.0, 1e-20],
            'large negative real'          => [-1e10, 0.0, -1.5707963266948965, 0.0],
            'large imaginary'              => [0.0, 1e10, 1.5707963267948966, 1e-10],
            'huge'                         => [1e200, 1e200, 1.5707963267948966, 5e-201],
            'huge negative'                => [-1e300, -1e-300, -1.5707963267948966, 0.0],
            'imaginary cut, right side'    => [0.0, 2.0, 1.5707963267948966, 0.5493061443340549],
            'imaginary cut, negative zero' => [-0.0, 2.0, 1.5707963267948966, 0.5493061443340549],
            'next to -i'                   => [1e-320, -1.0, 0.7853981633974483, -368.7601940357669],
        ];
    }

    /**
     * Test atan() against reference values: in every quadrant, at extreme magnitudes, and on each side of its branch
     * cut (the imaginary axis outside [-i, i]), where the sign of a zero part chooses the side.
     */
    #[DataProvider('atanReferenceProvider')]
    public function testAtanReferenceValues(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $result = new Complex($real, $imag)->atan();

        $this->assertApproxEqual($expectedReal, $result->real, 1e-14, 0.0);
        $this->assertApproxEqual($expectedImag, $result->imaginary, 1e-14, 0.0);
    }

    /**
     * Data provider for testAtanSingularityThrows().
     *
     * @return array<string, array{float, string}>
     */
    public static function atanSingularityProvider(): array
    {
        return [
            'i'  => [1.0, 'Cannot compute inverse tangent of i.'],
            '-i' => [-1.0, 'Cannot compute inverse tangent of -i.'],
        ];
    }

    /**
     * Test atan() throws at ±i, where it's undefined, with a message naming the function.
     */
    #[DataProvider('atanSingularityProvider')]
    public function testAtanSingularityThrows(float $imag, string $message): void
    {
        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage($message);
        new Complex(0, $imag)->atan();
    }

    #endregion
}
