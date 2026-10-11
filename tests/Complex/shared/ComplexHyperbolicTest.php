<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * Test sinh() throws when the result overflows.
     */
    public function testSinhOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute hyperbolic sine of Complex. The result would overflow.');
        new Complex(1000, 1)->sinh();
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

    /**
     * Test cosh() throws when the result overflows.
     */
    public function testCoshOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute hyperbolic cosine of Complex. The result would overflow.');
        new Complex(1000, 1)->cosh();
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

    /**
     * Test tanh() gives about ±1 for a large real part, instead of overflowing.
     */
    public function testTanhLargeRealPart(): void
    {
        // tanh(z) → 1 as Re(z) → ∞, and → -1 as Re(z) → -∞, where sinh(z) and cosh(z) both overflow.
        $result = new Complex(1000, 1)->tanh();
        $this->assertSame(1.0, $result->real);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        $result = new Complex(-1000)->tanh();
        $this->assertSame(-1.0, $result->real);
        $this->assertSame(0.0, $result->imaginary);
    }

    /**
     * Data provider for testTanhMatchesSinhOverCosh().
     *
     * @return array<string, array{float, float}>
     */
    public static function tanhValuesProvider(): array
    {
        return [
            'first quadrant'        => [0.3, 0.4],
            'second quadrant'       => [-1.2, 2.5],
            'fourth quadrant'       => [2.0, -0.7],
            'third quadrant'        => [-3.0, -3.0],
            'just below the cutoff' => [21.9, 1.0],
            'just above the cutoff' => [22.1, 1.0],
        ];
    }

    /**
     * Test tanh() matches sinh() / cosh() for ordinary values, either side of the large real part cutoff.
     */
    #[DataProvider('tanhValuesProvider')]
    public function testTanhMatchesSinhOverCosh(float $x, float $y): void
    {
        $z = new Complex($x, $y);
        $result = $z->tanh();
        $expected = $z->sinh()->div($z->cosh());

        $this->assertEqualsWithDelta($expected->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, EPSILON);
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
