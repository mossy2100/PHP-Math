<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Floats;
use OceanMoon\Math\Complex;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexInverseHyperbolicTest extends TestCase
{
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
     * Test asinh() throws when squaring the argument overflows.
     */
    public function testAsinhOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage(
            'Cannot compute inverse hyperbolic sine of the given Complex. The result would overflow.'
        );
        new Complex(1e200, 1e200)->asinh();
    }

    /**
     * Test asinh() gives a positive zero imaginary part when the phase underflows, as the phase is wrapped.
     */
    public function testAsinhUnderflowedPhaseIsPositiveZero(): void
    {
        $result = new Complex(1e75, -1e-300)->asinh();

        $this->assertTrue(Floats::isPositiveZero($result->imaginary));
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
     * Test acosh() of a large negative real number, where √(z² - 1) would cancel z exactly.
     */
    public function testAcoshLargeNegativeReal(): void
    {
        $result = new Complex(-1e10)->acosh();

        $this->assertEqualsWithDelta(23.7189981105004, $result->real, EPSILON);
        $this->assertEqualsWithDelta(M_PI, $result->imaginary, EPSILON);
    }

    /**
     * Test acosh() of a number whose square overflows, though the result is finite.
     */
    public function testAcoshLarge(): void
    {
        $result = new Complex(1e200, 1e200)->acosh();

        $this->assertEqualsWithDelta(461.55673936964905, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.7853981633974484, $result->imaginary, EPSILON);
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
     * Test atanh() throws when (1 + z) / (1 - z) overflows, for z very close to 1.
     */
    public function testAtanhOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage(
            'Cannot compute inverse hyperbolic tangent of the given Complex. The result would overflow.'
        );
        new Complex(1, 1e-320)->atanh();
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

    #endregion
}
