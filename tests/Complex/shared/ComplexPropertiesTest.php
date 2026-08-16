<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Math\M_I;

#[CoversClass(Complex::class)]
class ComplexPropertiesTest extends TestCase
{
    #region Property $real tests.

    /**
     * Test accessing the real property.
     */
    public function testRealProperty(): void
    {
        $z1 = new Complex(3, 4);
        $this->assertSame(3.0, $z1->real);

        $z2 = new Complex(-5.5, 2.3);
        $this->assertSame(-5.5, $z2->real);

        $z3 = new Complex(0, 1);
        $this->assertSame(0.0, $z3->real);
    }

    #endregion

    #region Property $imaginary tests.

    /**
     * Test accessing the imaginary property.
     */
    public function testImagProperty(): void
    {
        $z1 = new Complex(3, 4);
        $this->assertSame(4.0, $z1->imaginary);

        $z2 = new Complex(-5.5, 2.3);
        $this->assertSame(2.3, $z2->imaginary);

        $z3 = new Complex(5, 0);
        $this->assertSame(0.0, $z3->imaginary);
    }

    #endregion

    #region Property $magnitude tests.

    /**
     * Test magnitude property for complex numbers.
     */
    public function testMagnitudeComplex(): void
    {
        // 3-4-5 triangle
        $z1 = new Complex(3, 4);
        $this->assertEqualsWithDelta(5.0, $z1->magnitude, EPSILON);

        // 5-12-13 triangle
        $z2 = new Complex(5, 12);
        $this->assertEqualsWithDelta(13.0, $z2->magnitude, EPSILON);

        // Negative values
        $z3 = new Complex(-3, -4);
        $this->assertEqualsWithDelta(5.0, $z3->magnitude, EPSILON);

        // Arbitrary complex number
        $z4 = new Complex(1.5, 2.5);
        $this->assertEqualsWithDelta(hypot(1.5, 2.5), $z4->magnitude, EPSILON);
    }

    /**
     * Test magnitude property for real numbers.
     */
    public function testMagnitudeReal(): void
    {
        // Positive real
        $z1 = new Complex(5, 0);
        $this->assertEqualsWithDelta(5.0, $z1->magnitude, EPSILON);

        // Negative real
        $z2 = new Complex(-7, 0);
        $this->assertEqualsWithDelta(7.0, $z2->magnitude, EPSILON);

        // Zero
        $z3 = new Complex(0, 0);
        $this->assertEqualsWithDelta(0.0, $z3->magnitude, EPSILON);
    }

    /**
     * Test magnitude property for pure imaginary numbers.
     */
    public function testMagnitudePureImaginary(): void
    {
        // Positive imaginary
        $z1 = new Complex(0, 5);
        $this->assertEqualsWithDelta(5.0, $z1->magnitude, EPSILON);

        // Negative imaginary
        $z2 = new Complex(0, -7);
        $this->assertEqualsWithDelta(7.0, $z2->magnitude, EPSILON);
    }

    #endregion

    #region Property $phase tests.

    /**
     * Test phase property for complex numbers in each quadrant, and that it stays within the
     * principal range (-π, π].
     */
    public function testPhaseQuadrants(): void
    {
        // First quadrant (0 to π/2)
        $z1 = new Complex(1, 1);
        $this->assertEqualsWithDelta(M_PI / 4, $z1->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z1->phase);
        $this->assertLessThanOrEqual(M_PI, $z1->phase);

        // Second quadrant (π/2 to π)
        $z2 = new Complex(-1, 1);
        $this->assertEqualsWithDelta(3 * M_PI / 4, $z2->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z2->phase);
        $this->assertLessThanOrEqual(M_PI, $z2->phase);

        // Third quadrant (-π to -π/2)
        $z3 = new Complex(-1, -1);
        $this->assertEqualsWithDelta(-3 * M_PI / 4, $z3->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z3->phase);
        $this->assertLessThanOrEqual(M_PI, $z3->phase);

        // Fourth quadrant (-π/2 to 0)
        $z4 = new Complex(1, -1);
        $this->assertEqualsWithDelta(-M_PI / 4, $z4->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z4->phase);
        $this->assertLessThanOrEqual(M_PI, $z4->phase);
    }

    /**
     * Test phase property for real numbers.
     */
    public function testPhaseReal(): void
    {
        // Positive real (phase = 0)
        $z1 = new Complex(5, 0);
        $this->assertEqualsWithDelta(0.0, $z1->phase, EPSILON);

        // Negative real (phase = π)
        $z2 = new Complex(-5, 0);
        $this->assertEqualsWithDelta(M_PI, $z2->phase, EPSILON);

        // Zero (phase = 0)
        $z3 = new Complex(0, 0);
        $this->assertEqualsWithDelta(0.0, $z3->phase, EPSILON);
    }

    /**
     * Test phase property for pure imaginary numbers.
     */
    public function testPhasePureImaginary(): void
    {
        // Positive imaginary (phase = π/2)
        $z1 = new Complex(0, 1);
        $this->assertEqualsWithDelta(M_PI / 2, $z1->phase, EPSILON);

        // Negative imaginary (phase = -π/2)
        $z2 = new Complex(0, -1);
        $this->assertEqualsWithDelta(-M_PI / 2, $z2->phase, EPSILON);
    }

    /**
     * Test phase property on the axes (real/imaginary boundaries between quadrants).
     */
    public function testPhaseOnAxes(): void
    {
        $z1 = new Complex(5, 0);
        $this->assertEqualsWithDelta(0.0, $z1->phase, EPSILON);

        $z2 = new Complex(-5, 0);
        $this->assertEqualsWithDelta(M_PI, $z2->phase, EPSILON);

        $z3 = new Complex(0, 3);
        $this->assertEqualsWithDelta(M_PI / 2, $z3->phase, EPSILON);

        $z4 = new Complex(0, -3);
        $this->assertEqualsWithDelta(-M_PI / 2, $z4->phase, EPSILON);
    }

    /**
     * Test magnitude/phase computed from rectangular coordinates in the third quadrant.
     */
    public function testRectangularToPolarQuadrant3(): void
    {
        $z = new Complex(-3, -4);

        $this->assertEqualsWithDelta(5.0, $z->magnitude, EPSILON);
        // Phase should be in [-π, -π/2) range, specifically -(π - atan(4/3))
        $expectedPhase = -(M_PI - atan(4 / 3));
        $this->assertEqualsWithDelta($expectedPhase, $z->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z->phase);
        $this->assertLessThan(-M_PI / 2, $z->phase);
    }

    /**
     * Test magnitude/phase computed from rectangular coordinates in the fourth quadrant.
     */
    public function testRectangularToPolarQuadrant4(): void
    {
        $z = new Complex(3, -4);

        $this->assertEqualsWithDelta(5.0, $z->magnitude, EPSILON);
        // Phase should be in [-π/2, 0) range, specifically -atan(4/3)
        $expectedPhase = -atan(4 / 3);
        $this->assertEqualsWithDelta($expectedPhase, $z->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI / 2, $z->phase);
        $this->assertLessThan(0, $z->phase);
    }

    /**
     * Data provider for phase principal-range boundary cases.
     *
     * @return array<string, list<Complex>>
     */
    public static function phaseBoundaryProvider(): array
    {
        return [
            'positive real axis'      => [
                new Complex(1, 0),
            ],
            'negative real axis'      => [
                new Complex(-1, 0),
            ],
            'positive imaginary axis' => [
                new Complex(0, 1),
            ],
            'negative imaginary axis' => [
                new Complex(0, -1),
            ],
            'quadrant 1'              => [
                new Complex(1, 1),
            ],
            'quadrant 2'              => [
                new Complex(-1, 1),
            ],
            'quadrant 3'              => [
                new Complex(-1, -1),
            ],
            'quadrant 4'              => [
                new Complex(1, -1),
            ],
            'angle -π wraps to π'     => [
                Complex::fromPolar(1, -M_PI),
            ],
            'angle 5π wraps to π'     => [
                Complex::fromPolar(1, 5 * M_PI),
            ],
        ];
    }

    /**
     * Test that phase is always in the principal range (-π, π], across a variety of constructions.
     */
    #[DataProvider('phaseBoundaryProvider')]
    public function testPhaseAlwaysInPrincipalRange(Complex $z): void
    {
        $this->assertGreaterThan(-M_PI, $z->phase, "Phase should be > -π for $z");
        $this->assertLessThanOrEqual(M_PI, $z->phase, "Phase should be <= π for $z");
    }

    #endregion

    #region Constant M_I tests.

    /**
     * Test properties of the imaginary unit constant.
     */
    public function testImaginaryUnitProperties(): void
    {
        $z = M_I;

        $this->assertSame(0.0, $z->real);
        $this->assertSame(1.0, $z->imaginary);
        $this->assertEqualsWithDelta(1.0, $z->magnitude, EPSILON);
        $this->assertEqualsWithDelta(M_PI / 2, $z->phase, EPSILON);
    }

    #endregion
}
