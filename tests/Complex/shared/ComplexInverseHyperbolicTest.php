<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
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
        $this->expectExceptionMessage('Cannot compute inverse hyperbolic sine of Complex. The result would overflow.');
        new Complex(1e200, 1e200)->asinh();
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
            'Cannot compute inverse hyperbolic cosine of Complex. The result would overflow.'
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
            'Cannot compute inverse hyperbolic tangent of Complex. The result would overflow.'
        );
        new Complex(1, 1e-320)->atanh();
    }

    #endregion
}
