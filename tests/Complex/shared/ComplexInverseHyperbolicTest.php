<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

#[CoversClass(Complex::class)]
class ComplexInverseHyperbolicTest extends TestCase
{
    use FloatAssertions;

    #region Method asinh() tests.

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
     * Data provider for testAsinhReferenceValues(). The expected values are from Python's cmath.asinh().
     *
     * @return array<string, array{float, float, float, float}>
     */
    public static function asinhReferenceProvider(): array
    {
        return [
            'first quadrant'               => [1.5, 2.5, 1.7501487613175148, 1.0033627117332509],
            'second quadrant'              => [-1.5, 2.5, -1.7501487613175148, 1.0033627117332509],
            'third quadrant'               => [-1.5, -2.5, -1.7501487613175148, -1.0033627117332509],
            'fourth quadrant'              => [1.5, -2.5, 1.7501487613175148, -1.0033627117332509],
            'tiny real'                    => [1e-20, 0.0, 1e-20, 0.0],
            'tiny imaginary'               => [0.0, 1e-20, 0.0, 1e-20],
            'large negative real'          => [-1e10, 0.0, -23.7189981105004, 0.0],
            'large imaginary'              => [0.0, 1e10, 23.7189981105004, 1.5707963267948966],
            'huge'                         => [1e200, 1e200, 461.55673936964905, 0.7853981633974483],
            'huge negative'                => [-1e300, -1e-300, -691.4686750787736, 0.0],
            'large'                        => [1.0, 1e20, 46.74484904044086, 1.5707963267948966],
            'near the float limit'         => [1.0, 1.5e308, 710.2948209308341, 1.5707963267948966],
            'imaginary cut, right side'    => [0.0, 2.0, 1.3169578969248166, 1.5707963267948966],
            'imaginary cut, negative zero' => [-0.0, 2.0, 1.3169578969248166, 1.5707963267948966],
        ];
    }

    /**
     * Test asinh() against reference values: in every quadrant, at extreme magnitudes, and on each side of its branch
     * cut (the imaginary axis outside [-i, i]), where the sign of a zero part chooses the side.
     */
    #[DataProvider('asinhReferenceProvider')]
    public function testAsinhReferenceValues(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $result = new Complex($real, $imag)->asinh();

        $this->assertApproxEqual($expectedReal, $result->real, 1e-14, 0.0);
        $this->assertApproxEqual($expectedImag, $result->imaginary, 1e-14, 0.0);
    }

    /**
     * Test asinh() gives a positive zero when the imaginary part underflows, as Complex doesn't store negative zeros.
     */
    public function testAsinhUnderflowedImaginaryPartIsPositiveZero(): void
    {
        $result = new Complex(1e75, -1e-300)->asinh();

        $this->assertSame(0.0, $result->imaginary);
        $this->assertFalse(Floats::isNegativeZero($result->imaginary));
    }

    /**
     * Test asinh() matches PHP's asinh() on 5,000 real arguments from 1e-300 to 1e300 in magnitude, of both signs.
     */
    public function testAsinhMatchesRealFunction(): void
    {
        $this->assertMatchesRealFunction('asinh', self::randomReal(...));
    }

    #endregion

    #region Method acosh() tests.

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
     * Data provider for testAcoshPrincipalValue(). The expected values are from Python's cmath.acosh().
     *
     * @return array<string, array{float, float, float, float}>
     */
    public static function acoshPrincipalValueProvider(): array
    {
        return [
            'negative real'        => [-2.0, 0.0, 1.3169578969248166, 3.141592653589793],
            'second quadrant'      => [-2.0, 0.1, 1.3188765472109005, 3.083953449538381],
            'third quadrant'       => [-2.0, -0.1, 1.3188765472109005, -3.083953449538381],
            'third quadrant 3, 4'  => [-3.0, -4.0, 2.305509031243477, -2.2047801924340735],
            'second quadrant near' => [-0.5, 0.5, 0.5306375309525179, 2.0230747739460875],
            'third quadrant 1, 1'  => [-1.0, -1.0, 1.0612750619050357, -2.2370357592874117],
            'first quadrant'       => [1.0, 1.0, 1.0612750619050357, 0.9045568943023813],
        ];
    }

    /**
     * Test acosh() gives the principal value, whose real part is never negative, including for a negative real part.
     */
    #[DataProvider('acoshPrincipalValueProvider')]
    public function testAcoshPrincipalValue(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $result = new Complex($real, $imag)->acosh();

        $this->assertEqualsWithDelta($expectedReal, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $result->imaginary, EPSILON);
    }

    /**
     * Data provider for testAcoshReferenceValues(). The expected values are from Python's cmath.acosh().
     *
     * @return array<string, array{float, float, float, float}>
     */
    public static function acoshReferenceProvider(): array
    {
        return [
            'first quadrant'          => [1.5, 2.5, 1.777662377506645, 1.0552478910599898],
            'second quadrant'         => [-1.5, 2.5, 1.777662377506645, 2.0863447625298033],
            'third quadrant'          => [-1.5, -2.5, 1.777662377506645, -2.0863447625298033],
            'fourth quadrant'         => [1.5, -2.5, 1.777662377506645, -1.0552478910599898],
            'tiny real'               => [1e-20, 0.0, 0.0, 1.5707963267948966],
            'tiny imaginary'          => [0.0, 1e-20, 1e-20, 1.5707963267948966],
            'large negative real'     => [-1e10, 0.0, 23.7189981105004, 3.141592653589793],
            'large imaginary'         => [0.0, 1e10, 23.7189981105004, 1.5707963267948966],
            'huge'                    => [1e200, 1e200, 461.55673936964905, 0.7853981633974484],
            'huge negative'           => [-1e300, -1e-300, 691.4686750787736, -3.141592653589793],
            'real cut, upper side'    => [-2.0, 0.0, 1.3169578969248166, 3.141592653589793],
            'real cut, negative zero' => [-2.0, -0.0, 1.3169578969248166, 3.141592653589793],
        ];
    }

    /**
     * Test acosh() against reference values: in every quadrant, at extreme magnitudes, and on each side of its branch
     * cut (the real axis below 1), where the sign of a zero part chooses the side.
     */
    #[DataProvider('acoshReferenceProvider')]
    public function testAcoshReferenceValues(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $result = new Complex($real, $imag)->acosh();

        $this->assertApproxEqual($expectedReal, $result->real, 1e-14, 0.0);
        $this->assertApproxEqual($expectedImag, $result->imaginary, 1e-14, 0.0);
    }

    /**
     * Test acosh() matches PHP's acosh() on 5,000 real arguments of at least 1.
     */
    public function testAcoshMatchesRealFunction(): void
    {
        $this->assertMatchesRealFunction('acosh', self::randomAtLeastOne(...));
    }

    #endregion

    #region Method atanh() tests.

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

    /**
     * Data provider for testAtanhReferenceValues(). The expected values are from Python's cmath.atanh().
     *
     * @return array<string, array{float, float, float, float}>
     */
    public static function atanhReferenceProvider(): array
    {
        return [
            'first quadrant'          => [1.5, 2.5, 0.163481616851666, 1.2767950250211129],
            'second quadrant'         => [-1.5, 2.5, -0.163481616851666, 1.2767950250211129],
            'third quadrant'          => [-1.5, -2.5, -0.163481616851666, -1.2767950250211129],
            'fourth quadrant'         => [1.5, -2.5, 0.163481616851666, -1.2767950250211129],
            'tiny real'               => [1e-20, 0.0, 1e-20, 0.0],
            'tiny'                    => [1e-20, 1e-20, 1e-20, 1e-20],
            'tiny imaginary'          => [0.0, 1e-20, 0.0, 1e-20],
            'large negative real'     => [-1e10, 0.0, -1e-10, 1.5707963267948966],
            'large imaginary'         => [0.0, 1e10, 0.0, 1.5707963266948965],
            'huge'                    => [1e200, 1e200, 5e-201, 1.5707963267948966],
            'huge negative'           => [-1e300, -1e-300, -1e-300, -1.5707963267948966],
            'real cut, upper side'    => [2.0, 0.0, 0.5493061443340549, 1.5707963267948966],
            'real cut, negative zero' => [2.0, -0.0, 0.5493061443340549, 1.5707963267948966],
            'next to 1'               => [1.0, 1e-320, 368.7601940357669, 0.7853981633974483],
        ];
    }

    /**
     * Test atanh() against reference values: in every quadrant, at extreme magnitudes, and on each side of its branch
     * cut (the real axis outside [-1, 1]), where the sign of a zero part chooses the side.
     */
    #[DataProvider('atanhReferenceProvider')]
    public function testAtanhReferenceValues(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $result = new Complex($real, $imag)->atanh();

        $this->assertApproxEqual($expectedReal, $result->real, 1e-14, 0.0);
        $this->assertApproxEqual($expectedImag, $result->imaginary, 1e-14, 0.0);
    }

    /**
     * Data provider for testAtanhSingularityThrows().
     *
     * @return array<string, array{float, string}>
     */
    public static function atanhSingularityProvider(): array
    {
        return [
            'one'       => [1.0, 'Cannot compute inverse hyperbolic tangent of 1.'],
            'minus one' => [-1.0, 'Cannot compute inverse hyperbolic tangent of -1.'],
        ];
    }

    /**
     * Test atanh() throws at ±1, where it's undefined, with a message naming the function.
     */
    #[DataProvider('atanhSingularityProvider')]
    public function testAtanhSingularityThrows(float $real, string $message): void
    {
        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage($message);
        new Complex($real)->atanh();
    }

    /**
     * Test atanh() matches PHP's atanh() on 5,000 real arguments strictly between -1 and 1.
     */
    public function testAtanhMatchesRealFunction(): void
    {
        $this->assertMatchesRealFunction('atanh', self::randomInsideUnit(...));
    }

    #endregion

    #region Helper methods

    /**
     * Assert a Complex method matches PHP's function of the same name on real arguments.
     *
     * Each result must be real, and within a relative difference of 4 × PHP_FLOAT_EPSILON of PHP's. The arguments are
     * seeded, so a failure is reproducible, and every mismatch is listed.
     *
     * @param 'asin'|'acos'|'atan'|'asinh'|'acosh'|'atanh' $function The function name.
     * @param callable(Randomizer): float $generate Generates one argument.
     */
    private function assertMatchesRealFunction(string $function, callable $generate): void
    {
        $randomizer = new Randomizer(new Mt19937(1));
        $mismatches = [];
        for ($i = 0; $i < 5000; $i++) {
            $x = $generate($randomizer);
            $result = new Complex($x)->$function();
            $expected = $function($x);
            if (
                $result->imaginary !== 0.0
                || !Floats::approxEqual($expected, $result->real, 4 * PHP_FLOAT_EPSILON, 0.0)
            ) {
                $mismatches[] = "$function($x) gave $result, expected $expected.";
            }
        }

        $this->assertSame([], $mismatches);
    }

    /**
     * Generate 1 or -1.
     *
     * @param Randomizer $randomizer The seeded randomizer.
     * @return float The sign.
     */
    private static function randomSign(Randomizer $randomizer): float
    {
        return $randomizer->getInt(0, 1) === 0 ? 1.0 : -1.0;
    }

    /**
     * Generate a real number from 1e-300 to 1e300 in magnitude, of either sign.
     *
     * @param Randomizer $randomizer The seeded randomizer.
     * @return float The number.
     */
    private static function randomReal(Randomizer $randomizer): float
    {
        return self::randomSign($randomizer) * 10 ** $randomizer->getFloat(-300, 300);
    }

    /**
     * Generate a real number of at least 1: half from 1 to 1e300, and half within 1 of 1.
     *
     * @param Randomizer $randomizer The seeded randomizer.
     * @return float The number.
     */
    private static function randomAtLeastOne(Randomizer $randomizer): float
    {
        return $randomizer->getInt(0, 1) === 0
            ? 10 ** $randomizer->getFloat(0, 300)
            : 1 + 10 ** $randomizer->getFloat(-15, 0);
    }

    /**
     * Generate a real number strictly between -1 and 1: half from 1e-300 to 1 in magnitude, and half within 1e-15 of
     * ±1.
     *
     * @param Randomizer $randomizer The seeded randomizer.
     * @return float The number.
     */
    private static function randomInsideUnit(Randomizer $randomizer): float
    {
        $magnitude = $randomizer->getInt(0, 1) === 0
            ? 10 ** $randomizer->getFloat(-300, 0)
            : 1 - 10 ** $randomizer->getFloat(-15, 0);
        return self::randomSign($randomizer) * $magnitude;
    }

    #endregion
}
