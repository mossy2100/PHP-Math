<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Core\Exceptions\FormatException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexFactoryTest extends TestCase
{
    #region Method fromString() tests.

    /**
     * Test parsing pure real numbers
     */
    public function testFromStringRealNumbers(): void
    {
        $this->assertEquals(new Complex(5, 0), Complex::fromString('5'));
        $this->assertEquals(new Complex(-3.14, 0), Complex::fromString('-3.14'));
        $this->assertEquals(new Complex(0, 0), Complex::fromString('0'));
        $this->assertEquals(new Complex(123.0, 0), Complex::fromString('123.'));
        $this->assertEquals(new Complex(0.45, 0), Complex::fromString('.45'));
        $this->assertEquals(new Complex(1.23e4, 0), Complex::fromString('1.23e4'));
        $this->assertEquals(new Complex(-2.5e-3, 0), Complex::fromString('-2.5e-3'));
    }

    /**
     * Test parsing pure imaginary numbers
     */
    public function testFromStringPureImaginary(): void
    {
        // Basic imaginary units
        $this->assertEquals(new Complex(0, 1), Complex::fromString('i'));
        $this->assertEquals(new Complex(0, 1), Complex::fromString('I'));

        // Negative imaginary units
        $this->assertEquals(new Complex(0, -1), Complex::fromString('-i'));
        $this->assertEquals(new Complex(0, -1), Complex::fromString('-I'));

        // Imaginary with coefficients
        $this->assertEquals(new Complex(0, 3), Complex::fromString('3i'));
        $this->assertEquals(new Complex(0, -2.5), Complex::fromString('-2.5i'));
        $this->assertEquals(new Complex(0, 0.75), Complex::fromString('0.75I'));
        $this->assertEquals(new Complex(0, 1.5e2), Complex::fromString('1.5e2I'));
    }

    /**
     * Test parsing complex numbers (real + imaginary)
     */
    public function testFromStringComplexRealFirst(): void
    {
        // Standard format: a+bi
        $this->assertEquals(new Complex(3, 4), Complex::fromString('3+4i'));
        $this->assertEquals(new Complex(5, -2), Complex::fromString('5-2i'));
        $this->assertEquals(new Complex(-1, 1), Complex::fromString('-1+i'));
        $this->assertEquals(new Complex(2.5, -3.7), Complex::fromString('2.5-3.7I'));

        // With decimals and scientific notation
        $this->assertEquals(new Complex(1.23, 4.56), Complex::fromString('1.23+4.56i'));
        $this->assertEquals(new Complex(-0.5, 2.5e-1), Complex::fromString('-0.5+2.5e-1i'));
        $this->assertEquals(new Complex(123.0, -1), Complex::fromString('123.-I'));
    }

    /**
     * Test parsing complex numbers (imaginary + real)
     */
    public function testFromStringComplexImagFirst(): void
    {
        // Standard format: bi+a
        $this->assertEquals(new Complex(3, 4), Complex::fromString('4i+3'));
        $this->assertEquals(new Complex(5, -2), Complex::fromString('-2i+5'));
        $this->assertEquals(new Complex(-1, 1), Complex::fromString('i-1'));
        $this->assertEquals(new Complex(2.5, -3.7), Complex::fromString('-3.7I+2.5'));

        // With decimals and scientific notation
        $this->assertEquals(new Complex(1.23, 4.56), Complex::fromString('4.56i+1.23'));
        $this->assertEquals(new Complex(-0.5, 2.5e-1), Complex::fromString('2.5e-1i-0.5'));
    }

    /**
     * Test parsing with whitespace (should be stripped)
     */
    public function testFromStringWithWhitespace(): void
    {
        $this->assertEquals(new Complex(3, 4), Complex::fromString(' 3 + 4i '));
        $this->assertEquals(new Complex(5, -2), Complex::fromString('5 - 2i'));
        $this->assertEquals(new Complex(-1, 1), Complex::fromString(' -1 + i'));
        $this->assertEquals(new Complex(3, 4), Complex::fromString('4i + 3'));
        $this->assertEquals(new Complex(0, 1), Complex::fromString(' i '));
        $this->assertEquals(new Complex(5, 0), Complex::fromString(' 5 '));
    }

    /**
     * Test edge cases with coefficients
     */
    public function testFromStringCoefficientEdgeCases(): void
    {
        // Explicit positive signs
        $this->assertEquals(new Complex(0, 1), Complex::fromString('+i'));

        // Zero coefficients
        $this->assertEquals(new Complex(0, 0), Complex::fromString('0i'));
        $this->assertEquals(new Complex(0, 0), Complex::fromString('0+0i'));

        // Trailing decimal points
        $this->assertEquals(new Complex(3, 4), Complex::fromString('3.+4.i'));
    }

    /**
     * Data provider for invalid input strings.
     *
     * @return array<string, list<string>>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'empty string'                        => [''],
            'random text'                         => ['abc'],
            'incomplete expression'               => ['3+'],
            'double signs'                        => ['++i'],
            'missing imaginary unit'              => ['3+4'],
            'incomplete imaginary'                => ['i+'],
            'wrong imaginary unit'                => ['3+4k'],
            'j is not a supported imaginary unit' => ['3+4j'],
            'multiple decimal points'             => ['3.4.5'],
            'incomplete scientific notation'      => ['3e'],
            'double e'                            => ['3ee4'],
        ];
    }

    /**
     * Test parsing invalid input throws FormatException.
     *
     * @param string $input The invalid input string.
     */
    #[DataProvider('invalidInputProvider')]
    public function testFromStringInvalidInput(string $input): void
    {
        $this->expectException(FormatException::class);
        Complex::fromString($input);
    }

    /**
     * Test parsing returns new Complex instances
     */
    public function testFromStringReturnsNewInstances(): void
    {
        $c1 = Complex::fromString('3+4i');
        $c2 = Complex::fromString('3+4i');

        $this->assertEquals($c1, $c2);
        $this->assertNotSame($c1, $c2); // Different object instances
    }

    /**
     * Test scientific notation edge cases
     */
    public function testFromStringScientificNotation(): void
    {
        $this->assertEquals(new Complex(1.5e10, 0), Complex::fromString('1.5e10'));
        $this->assertEquals(new Complex(0, -2.3e-5), Complex::fromString('-2.3e-5i'));
        $this->assertEquals(new Complex(1e5, 2e-3), Complex::fromString('1e5+2e-3i'));
        $this->assertEquals(new Complex(-1.5, 3.2e4), Complex::fromString('3.2e4i-1.5'));
    }

    /**
     * Test that a numeric string parsing to a non-finite float (overflow to INF) throws.
     */
    public function testFromStringOverflowThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromString('1e400');
    }

    /**
     * Data provider for comprehensive format testing
     *
     * @return array<array{string, float, float}>
     */
    public static function complexNumberProvider(): array
    {
        return [
            // [input_string, expected_real, expected_imag]
            ['0', 0, 0],
            ['5', 5, 0],
            ['-3.14', -3.14, 0],
            ['i', 0, 1],
            ['-i', 0, -1],
            ['3i', 0, 3],
            ['-2.5i', 0, -2.5],
            ['3+4i', 3, 4],
            ['5-2i', 5, -2],
            ['-1+i', -1, 1],
            ['4i+3', 3, 4],
            ['-2i+5', 5, -2],
            ['i-1', -1, 1],
            [' 3 + 4i ', 3, 4],
            ['1.5e2+3.2e-1i', 150, 0.32],
        ];
    }

    #[DataProvider('complexNumberProvider')]
    public function testFromStringComprehensive(string $input, float $expectedReal, float $expectedImag): void
    {
        $result = Complex::fromString($input);
        $expected = new Complex($expectedReal, $expectedImag);

        $this->assertEquals($expected, $result);
        $this->assertEqualsWithDelta($expectedReal, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $result->imaginary, EPSILON);
    }

    #endregion

    #region Method fromPolar() tests.

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
     * Test fromPolar with various angles produces the correct real/imaginary parts.
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
     * Test fromPolar with an infinite magnitude throws exception.
     */
    public function testFromPolarInfiniteMagnitude(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromPolar(INF, M_PI / 4);
    }

    /**
     * Test fromPolar with an infinite phase throws exception.
     */
    public function testFromPolarInfinitePhase(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromPolar(5, INF);
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
     * Test that negative angles already within the principal range are preserved as-is.
     */
    public function testFromPolarNegativePhasePreserved(): void
    {
        $z = Complex::fromPolar(1, -M_PI / 4);
        $this->assertEqualsWithDelta(-M_PI / 4, $z->phase, EPSILON);

        $z2 = Complex::fromPolar(1, -M_PI / 2);
        $this->assertEqualsWithDelta(-M_PI / 2, $z2->phase, EPSILON);
    }

    /**
     * Test that angles > π wrap to the principal range (-π, π].
     */
    public function testFromPolarLargePositivePhaseNormalized(): void
    {
        $z = Complex::fromPolar(1, 3 * M_PI);
        $this->assertEqualsWithDelta(M_PI, $z->phase, EPSILON);

        $z2 = Complex::fromPolar(1, 3 * M_PI / 2);
        $this->assertEqualsWithDelta(-M_PI / 2, $z2->phase, EPSILON);
    }

    /**
     * Test that very large positive angles wrap correctly.
     *
     * Avoids landing exactly on ±π boundaries to prevent floating-point precision issues.
     */
    public function testFromPolarVeryLargePhaseNormalized(): void
    {
        $z = Complex::fromPolar(1, 10.5 * M_PI);
        $this->assertEqualsWithDelta(M_PI / 2, $z->phase, EPSILON);

        $z2 = Complex::fromPolar(1, 11.25 * M_PI);
        $this->assertEqualsWithDelta(-3 * M_PI / 4, $z2->phase, EPSILON);
    }

    /**
     * Test that very large negative angles wrap correctly.
     *
     * Avoids landing exactly on ±π boundaries to prevent floating-point precision issues.
     */
    public function testFromPolarVeryLargeNegativePhaseNormalized(): void
    {
        $z = Complex::fromPolar(1, -10.5 * M_PI);
        $this->assertEqualsWithDelta(-M_PI / 2, $z->phase, EPSILON);

        $z2 = Complex::fromPolar(1, -11.25 * M_PI);
        $this->assertEqualsWithDelta(3 * M_PI / 4, $z2->phase, EPSILON);
    }

    /**
     * Test magnitude/phase round-trip through fromPolar() for each quadrant.
     */
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

    /**
     * Test that converting from polar back to rectangular form is consistent.
     */
    public function testPolarToRectangularConsistency(): void
    {
        $mag = 10.0;
        $phase = -M_PI / 3;  // -60 degrees (quadrant 4)

        $z = Complex::fromPolar($mag, $phase);

        $expectedReal = $mag * cos($phase);
        $expectedImag = $mag * sin($phase);

        $this->assertEqualsWithDelta($expectedReal, $z->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $z->imaginary, EPSILON);
    }

    /**
     * Test that fromPolar correctly sets the magnitude/phase computed properties directly, rather
     * than leaving them to be lazily recomputed from real/imaginary.
     */
    public function testFromPolarSetsCachedValues(): void
    {
        $mag = 5.0;
        $phase = M_PI / 3;

        $z = Complex::fromPolar($mag, $phase);

        $this->assertEqualsWithDelta($mag, $z->magnitude, EPSILON);
        $this->assertEqualsWithDelta($phase, $z->phase, EPSILON);
        $this->assertEqualsWithDelta($mag * cos($phase), $z->real, EPSILON);
        $this->assertEqualsWithDelta($mag * sin($phase), $z->imaginary, EPSILON);
    }

    /**
     * Test that fromPolar preserves phase correctly for angles already in the principal range
     * (-π, π], including the -π wrap-to-π boundary case.
     */
    public function testFromPolarWithVariousAnglesPhase(): void
    {
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

    #endregion
}
