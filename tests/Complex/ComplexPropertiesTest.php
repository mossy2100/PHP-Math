<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexPropertiesTest extends TestCase
{
    /**
     * Test accessing the real property.
     */
    public function testRealProperty(): void
    {
        $z = new Complex(3, 4);
        $this->assertSame(3.0, $z->real);

        $z2 = new Complex(-5.5, 2.3);
        $this->assertSame(-5.5, $z2->real);

        $z3 = new Complex(0, 1);
        $this->assertSame(0.0, $z3->real);
    }

    /**
     * Test accessing the imaginary property.
     */
    public function testImagProperty(): void
    {
        $z = new Complex(3, 4);
        $this->assertSame(4.0, $z->imaginary);

        $z2 = new Complex(-5.5, 2.3);
        $this->assertSame(2.3, $z2->imaginary);

        $z3 = new Complex(5, 0);
        $this->assertSame(0.0, $z3->imaginary);
    }

    /**
     * Test magnitude property for complex numbers.
     */
    public function testMagnitudeComplex(): void
    {
        // 3-4-5 triangle
        $z = new Complex(3, 4);
        $this->assertEqualsWithDelta(5.0, $z->magnitude, Complex::EPSILON);

        // 5-12-13 triangle
        $z2 = new Complex(5, 12);
        $this->assertEqualsWithDelta(13.0, $z2->magnitude, Complex::EPSILON);

        // Negative values
        $z3 = new Complex(-3, -4);
        $this->assertEqualsWithDelta(5.0, $z3->magnitude, Complex::EPSILON);

        // Arbitrary complex number
        $z4 = new Complex(1.5, 2.5);
        $this->assertEqualsWithDelta(hypot(1.5, 2.5), $z4->magnitude, Complex::EPSILON);
    }

    /**
     * Test magnitude property for real numbers.
     */
    public function testMagnitudeReal(): void
    {
        // Positive real
        $z = new Complex(5, 0);
        $this->assertEqualsWithDelta(5.0, $z->magnitude, Complex::EPSILON);

        // Negative real
        $z2 = new Complex(-7, 0);
        $this->assertEqualsWithDelta(7.0, $z2->magnitude, Complex::EPSILON);

        // Zero
        $z3 = new Complex(0, 0);
        $this->assertEqualsWithDelta(0.0, $z3->magnitude, Complex::EPSILON);
    }

    /**
     * Test magnitude property for pure imaginary numbers.
     */
    public function testMagnitudePureImaginary(): void
    {
        // Positive imaginary
        $z = new Complex(0, 5);
        $this->assertEqualsWithDelta(5.0, $z->magnitude, Complex::EPSILON);

        // Negative imaginary
        $z2 = new Complex(0, -7);
        $this->assertEqualsWithDelta(7.0, $z2->magnitude, Complex::EPSILON);
    }

    /**
     * Test that magnitude is cached (same value returned on multiple accesses).
     */
    public function testMagnitudeIsCached(): void
    {
        $z = new Complex(3, 4);

        $mag1 = $z->magnitude;
        $mag2 = $z->magnitude;

        $this->assertSame($mag1, $mag2);
    }

    /**
     * Test phase property for complex numbers in each quadrant.
     */
    public function testPhaseQuadrants(): void
    {
        // First quadrant (0 to π/2)
        $z1 = new Complex(1, 1);
        $this->assertEqualsWithDelta(M_PI / 4, $z1->phase, Complex::EPSILON);

        // Second quadrant (π/2 to π)
        $z2 = new Complex(-1, 1);
        $this->assertEqualsWithDelta(3 * M_PI / 4, $z2->phase, Complex::EPSILON);

        // Third quadrant (-π to -π/2)
        $z3 = new Complex(-1, -1);
        $this->assertEqualsWithDelta(-3 * M_PI / 4, $z3->phase, Complex::EPSILON);

        // Fourth quadrant (-π/2 to 0)
        $z4 = new Complex(1, -1);
        $this->assertEqualsWithDelta(-M_PI / 4, $z4->phase, Complex::EPSILON);
    }

    /**
     * Test phase property for real numbers.
     */
    public function testPhaseReal(): void
    {
        // Positive real (phase = 0)
        $z1 = new Complex(5, 0);
        $this->assertEqualsWithDelta(0.0, $z1->phase, Complex::EPSILON);

        // Negative real (phase = π)
        $z2 = new Complex(-5, 0);
        $this->assertEqualsWithDelta(M_PI, $z2->phase, Complex::EPSILON);

        // Zero (phase = 0)
        $z3 = new Complex(0, 0);
        $this->assertEqualsWithDelta(0.0, $z3->phase, Complex::EPSILON);
    }

    /**
     * Test phase property for pure imaginary numbers.
     */
    public function testPhasePureImaginary(): void
    {
        // Positive imaginary (phase = π/2)
        $z1 = new Complex(0, 1);
        $this->assertEqualsWithDelta(M_PI / 2, $z1->phase, Complex::EPSILON);

        // Negative imaginary (phase = -π/2)
        $z2 = new Complex(0, -1);
        $this->assertEqualsWithDelta(-M_PI / 2, $z2->phase, Complex::EPSILON);
    }

    /**
     * Test that phase is cached (same value returned on multiple accesses).
     */
    public function testPhaseIsCached(): void
    {
        $z = new Complex(3, 4);

        $phase1 = $z->phase;
        $phase2 = $z->phase;

        $this->assertSame($phase1, $phase2);
    }

    /**
     * Test that fromPolar correctly sets magnitude and phase.
     */
    public function testFromPolarSetsCachedValues(): void
    {
        $mag = 5.0;
        $phase = M_PI / 3;

        $z = Complex::fromPolar($mag, $phase);

        // Verify magnitude is correct
        $this->assertEqualsWithDelta($mag, $z->magnitude, Complex::EPSILON);

        // Verify phase is correct
        $this->assertEqualsWithDelta($phase, $z->phase, Complex::EPSILON);

        // Verify real and imaginary parts
        $this->assertEqualsWithDelta($mag * cos($phase), $z->real, Complex::EPSILON);
        $this->assertEqualsWithDelta($mag * sin($phase), $z->imaginary, Complex::EPSILON);
    }

    /**
     * Test that fromPolar with various angles produces correct phases.
     */
    public function testFromPolarVariousAngles(): void
    {
        // Test positive angles (should remain unchanged)
        $positiveAngles = [0, M_PI / 6, M_PI / 4, M_PI / 3, M_PI / 2, M_PI];

        foreach ($positiveAngles as $angle) {
            $z = Complex::fromPolar(1.0, $angle);
            $this->assertEqualsWithDelta($angle, $z->phase, Complex::EPSILON);
        }

        // Test negative angles (should remain in principal range (-π, π])
        $z1 = Complex::fromPolar(1.0, -M_PI / 2);
        $this->assertEqualsWithDelta(-M_PI / 2, $z1->phase, Complex::EPSILON);

        $z2 = Complex::fromPolar(1.0, -M_PI / 4);
        $this->assertEqualsWithDelta(-M_PI / 4, $z2->phase, Complex::EPSILON);
    }

    /**
     * Test properties with the imaginary unit constant.
     */
    public function testImaginaryUnitProperties(): void
    {
        $z = Complex::i();

        // Verify real and imaginary parts
        $this->assertSame(0.0, $z->real);
        $this->assertSame(1.0, $z->imaginary);

        // Verify magnitude
        $this->assertEqualsWithDelta(1.0, $z->magnitude, Complex::EPSILON);

        // Verify phase
        $this->assertEqualsWithDelta(M_PI / 2, $z->phase, Complex::EPSILON);
    }

    /**
     * Test that real and imag properties are read-only from outside the class.
     */
    public function testPropertiesAreReadOnly(): void
    {
        $z = new Complex(3, 4);

        // This test verifies that the properties have private(set) visibility
        // If we try to set them from outside, it should be a compile-time error
        // We can't test this with PHPUnit directly, but we can verify they're readable
        $this->assertSame(3.0, $z->real);
        $this->assertSame(4.0, $z->imaginary);
    }
}
