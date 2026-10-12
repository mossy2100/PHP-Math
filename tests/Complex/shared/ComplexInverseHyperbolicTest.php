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
     * Test acosh() throws when squaring the argument overflows.
     */
    public function testAcoshOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage(
            'Cannot compute inverse hyperbolic cosine of the given Complex. The result would overflow.'
        );
        new Complex(1e200, 1e200)->acosh();
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
