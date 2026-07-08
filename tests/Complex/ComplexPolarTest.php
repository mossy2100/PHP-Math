<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexPolarTest extends TestCase
{
    /**
     * Test fromPolar with positive magnitude.
     */
    public function testFromPolarPositive(): void
    {
        $mag = 5.0;
        $phase = M_PI / 3;

        $z = Complex::fromPolar($mag, $phase);

        $this->assertEqualsWithDelta($mag * cos($phase), $z->real, EPSILON);
        $this->assertEqualsWithDelta($mag * sin($phase), $z->imaginary, EPSILON);
        $this->assertEqualsWithDelta($mag, $z->magnitude, EPSILON);
        $this->assertEqualsWithDelta($phase, $z->phase, EPSILON);
    }

    /**
     * Test fromPolar with zero magnitude.
     */
    public function testFromPolarZero(): void
    {
        $z = Complex::fromPolar(0, M_PI / 4);

        $this->assertSame(0.0, $z->real);
        $this->assertSame(0.0, $z->imaginary);
    }

    /**
     * Test fromPolar with various angles.
     */
    public function testFromPolarVariousAngles(): void
    {
        $angles = [0, M_PI / 6, M_PI / 4, M_PI / 3, M_PI / 2, M_PI, -M_PI / 2, -M_PI];

        foreach ($angles as $angle) {
            $z = Complex::fromPolar(1.0, $angle);
            $this->assertEqualsWithDelta(cos($angle), $z->real, EPSILON);
            $this->assertEqualsWithDelta(sin($angle), $z->imaginary, EPSILON);
        }
    }

    /**
     * Test fromPolar with negative magnitude throws exception.
     */
    public function testFromPolarNegativeMagnitude(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromPolar(-5, M_PI / 4);
    }

    /**
     * Test fromPolar accepts int or float.
     */
    public function testFromPolarIntFloat(): void
    {
        $z1 = Complex::fromPolar(5, M_PI / 4);
        $this->assertInstanceOf(Complex::class, $z1);

        $z2 = Complex::fromPolar(5.0, M_PI / 4);
        $this->assertInstanceOf(Complex::class, $z2);

        $z3 = Complex::fromPolar(5, 0.785398163);
        $this->assertInstanceOf(Complex::class, $z3);

        $z4 = Complex::fromPolar(5.0, 0.785398163);
        $this->assertInstanceOf(Complex::class, $z4);
    }

    /**
     * Test that phase is in principal range [-π, π] for all quadrants.
     */
    public function testPhaseInPrincipalRangeQuadrant1(): void
    {
        // Quadrant 1: positive real, positive imaginary → [0, π/2)
        $z = new Complex(1, 1);
        $this->assertEqualsWithDelta(M_PI / 4, $z->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z->phase);
        $this->assertLessThanOrEqual(M_PI, $z->phase);
    }

    public function testPhaseInPrincipalRangeQuadrant2(): void
    {
        // Quadrant 2: negative real, positive imaginary → [π/2, π]
        $z = new Complex(-1, 1);
        $this->assertEqualsWithDelta(3 * M_PI / 4, $z->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z->phase);
        $this->assertLessThanOrEqual(M_PI, $z->phase);
    }

    public function testPhaseInPrincipalRangeQuadrant3(): void
    {
        // Quadrant 3: negative real, negative imaginary → [-π, -π/2)
        $z = new Complex(-1, -1);
        $this->assertEqualsWithDelta(-3 * M_PI / 4, $z->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z->phase);
        $this->assertLessThanOrEqual(M_PI, $z->phase);
    }

    public function testPhaseInPrincipalRangeQuadrant4(): void
    {
        // Quadrant 4: positive real, negative imaginary → [-π/2, 0)
        $z = new Complex(1, -1);
        $this->assertEqualsWithDelta(-M_PI / 4, $z->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z->phase);
        $this->assertLessThanOrEqual(M_PI, $z->phase);
    }

    public function testPhasePositiveRealAxis(): void
    {
        $z = new Complex(5, 0);
        $this->assertEqualsWithDelta(0.0, $z->phase, EPSILON);
    }

    public function testPhaseNegativeRealAxis(): void
    {
        $z = new Complex(-5, 0);
        $this->assertEqualsWithDelta(M_PI, $z->phase, EPSILON);
    }

    public function testPhasePositiveImaginaryAxis(): void
    {
        $z = new Complex(0, 3);
        $this->assertEqualsWithDelta(M_PI / 2, $z->phase, EPSILON);
    }

    public function testPhaseNegativeImaginaryAxis(): void
    {
        $z = new Complex(0, -3);
        $this->assertEqualsWithDelta(-M_PI / 2, $z->phase, EPSILON);
    }

    public function testFromPolarNegativePhasePreserved(): void
    {
        // Negative angles in [-π, π] should be preserved
        $z = Complex::fromPolar(1, -M_PI / 4);
        $this->assertEqualsWithDelta(-M_PI / 4, $z->phase, EPSILON);

        $z2 = Complex::fromPolar(1, -M_PI / 2);
        $this->assertEqualsWithDelta(-M_PI / 2, $z2->phase, EPSILON);
    }

    public function testFromPolarLargePositivePhaseNormalized(): void
    {
        // Angles > π should wrap to (-π, π]
        $z = Complex::fromPolar(1, 3 * M_PI);
        $this->assertEqualsWithDelta(M_PI, $z->phase, EPSILON);

        $z2 = Complex::fromPolar(1, 3 * M_PI / 2);
        $this->assertEqualsWithDelta(-M_PI / 2, $z2->phase, EPSILON);
    }

    public function testFromPolarVeryLargePhaseNormalized(): void
    {
        // Very large angles should wrap correctly
        // Avoid landing exactly on ±π boundaries to prevent FP precision issues
        $z = Complex::fromPolar(1, 10.5 * M_PI);
        $this->assertEqualsWithDelta(M_PI / 2, $z->phase, EPSILON);

        $z2 = Complex::fromPolar(1, 11.25 * M_PI);
        $this->assertEqualsWithDelta(-3 * M_PI / 4, $z2->phase, EPSILON);
    }

    public function testFromPolarVeryLargeNegativePhaseNormalized(): void
    {
        // Very large negative angles should wrap correctly
        // Avoid landing exactly on ±π boundaries to prevent FP precision issues
        $z = Complex::fromPolar(1, -10.5 * M_PI);
        $this->assertEqualsWithDelta(-M_PI / 2, $z->phase, EPSILON);

        $z2 = Complex::fromPolar(1, -11.25 * M_PI);
        $this->assertEqualsWithDelta(3 * M_PI / 4, $z2->phase, EPSILON);
    }

    public function testFromPolarRoundTripQuadrant1(): void
    {
        $mag = 5.0;
        $phase = M_PI / 6;  // 30 degrees
        $z = Complex::fromPolar($mag, $phase);

        $this->assertEqualsWithDelta($mag, $z->magnitude, EPSILON);
        $this->assertEqualsWithDelta($phase, $z->phase, EPSILON);
    }

    public function testFromPolarRoundTripQuadrant2(): void
    {
        $mag = 5.0;
        $phase = 2 * M_PI / 3;  // 120 degrees
        $z = Complex::fromPolar($mag, $phase);

        $this->assertEqualsWithDelta($mag, $z->magnitude, EPSILON);
        $this->assertEqualsWithDelta($phase, $z->phase, EPSILON);
    }

    public function testFromPolarRoundTripQuadrant3(): void
    {
        $mag = 5.0;
        $phase = -5 * M_PI / 6;  // -150 degrees
        $z = Complex::fromPolar($mag, $phase);

        $this->assertEqualsWithDelta($mag, $z->magnitude, EPSILON);
        $this->assertEqualsWithDelta($phase, $z->phase, EPSILON);
    }

    public function testFromPolarRoundTripQuadrant4(): void
    {
        $mag = 5.0;
        $phase = -M_PI / 4;  // -45 degrees
        $z = Complex::fromPolar($mag, $phase);

        $this->assertEqualsWithDelta($mag, $z->magnitude, EPSILON);
        $this->assertEqualsWithDelta($phase, $z->phase, EPSILON);
    }

    public function testRectangularToPolarQuadrant3(): void
    {
        // Create from rectangular, verify polar form is correct
        $z = new Complex(-3, -4);

        $this->assertEqualsWithDelta(5.0, $z->magnitude, EPSILON);
        // Phase should be in [-π, -π/2) range, specifically -(π - atan(4/3))
        $expectedPhase = -(M_PI - atan(4 / 3));
        $this->assertEqualsWithDelta($expectedPhase, $z->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI, $z->phase);
        $this->assertLessThan(-M_PI / 2, $z->phase);
    }

    public function testRectangularToPolarQuadrant4(): void
    {
        // Create from rectangular, verify polar form is correct
        $z = new Complex(3, -4);

        $this->assertEqualsWithDelta(5.0, $z->magnitude, EPSILON);
        // Phase should be in [-π/2, 0) range, specifically -atan(4/3)
        $expectedPhase = -atan(4 / 3);
        $this->assertEqualsWithDelta($expectedPhase, $z->phase, EPSILON);
        $this->assertGreaterThanOrEqual(-M_PI / 2, $z->phase);
        $this->assertLessThan(0, $z->phase);
    }

    public function testPolarToRectangularConsistency(): void
    {
        // Create from polar, convert back to rectangular, verify consistency
        $mag = 10.0;
        $phase = -M_PI / 3;  // -60 degrees (quadrant 4)

        $z = Complex::fromPolar($mag, $phase);

        $expectedReal = $mag * cos($phase);
        $expectedImag = $mag * sin($phase);

        $this->assertEqualsWithDelta($expectedReal, $z->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $z->imaginary, EPSILON);
    }

    public function testPhaseAlwaysInPrincipalRange(): void
    {
        // Test various constructions to ensure phase is always (-π, π]
        $testCases = [
            new Complex(1, 0),
            new Complex(-1, 0),
            new Complex(0, 1),
            new Complex(0, -1),
            new Complex(1, 1),
            new Complex(-1, 1),
            new Complex(-1, -1),
            new Complex(1, -1),
            Complex::fromPolar(1, -M_PI),
            Complex::fromPolar(1, 5 * M_PI),
        ];

        foreach ($testCases as $z) {
            $this->assertGreaterThan(-M_PI, $z->phase, "Phase should be > -π for $z");
            $this->assertLessThanOrEqual(M_PI, $z->phase, "Phase should be <= π for $z");
        }
    }

    public function testFromPolarSetsCachedValues(): void
    {
        $mag = 5.0;
        $phase = M_PI / 3;

        $z = Complex::fromPolar($mag, $phase);

        // Verify magnitude is correct
        $this->assertEqualsWithDelta($mag, $z->magnitude, EPSILON);

        // Verify phase is correct
        $this->assertEqualsWithDelta($phase, $z->phase, EPSILON);

        // Verify real and imaginary parts
        $this->assertEqualsWithDelta($mag * cos($phase), $z->real, EPSILON);
        $this->assertEqualsWithDelta($mag * sin($phase), $z->imaginary, EPSILON);
    }

    public function testFromPolarWithVariousAnglesPhase(): void
    {
        // Test that fromPolar preserves phase correctly for angles in the principal range (-π, π].
        $testCases = [
            [0, 0],
            [M_PI / 6, M_PI / 6],
            [M_PI / 4, M_PI / 4],
            [M_PI / 3, M_PI / 3],
            [M_PI / 2, M_PI / 2],
            [M_PI, M_PI],
            [-M_PI / 2, -M_PI / 2],
            [-M_PI, M_PI],  // -π wraps to π (excluded lower bound)
        ];

        foreach ($testCases as [$inputAngle, $expectedPhase]) {
            $z = Complex::fromPolar(1.0, $inputAngle);
            $this->assertEqualsWithDelta(
                $expectedPhase,
                $z->phase,
                EPSILON,
                "Phase should be $expectedPhase for input angle $inputAngle"
            );
        }
    }
}
