<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Core\Exceptions\FormatException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Math\M_I;

#[CoversClass(Complex::class)]
class ComplexFactoryTest extends TestCase
{
    #region M_I constant tests

    /**
     * Test the imaginary unit constant.
     */
    public function testImaginaryUnitConstant(): void
    {
        $this->assertSame(0.0, M_I->real);
        $this->assertSame(1.0, M_I->imaginary);
    }

    #endregion

    #region fromString tests

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
        $this->assertEquals(new Complex(0, 1), Complex::fromString('j'));
        $this->assertEquals(new Complex(0, 1), Complex::fromString('I'));
        $this->assertEquals(new Complex(0, 1), Complex::fromString('J'));

        // Negative imaginary units
        $this->assertEquals(new Complex(0, -1), Complex::fromString('-i'));
        $this->assertEquals(new Complex(0, -1), Complex::fromString('-j'));
        $this->assertEquals(new Complex(0, -1), Complex::fromString('-I'));
        $this->assertEquals(new Complex(0, -1), Complex::fromString('-J'));

        // Imaginary with coefficients
        $this->assertEquals(new Complex(0, 3), Complex::fromString('3i'));
        $this->assertEquals(new Complex(0, -2.5), Complex::fromString('-2.5j'));
        $this->assertEquals(new Complex(0, 0.75), Complex::fromString('0.75I'));
        $this->assertEquals(new Complex(0, 1.5e2), Complex::fromString('1.5e2J'));
    }

    /**
     * Test parsing complex numbers (real + imaginary)
     */
    public function testFromStringComplexRealFirst(): void
    {
        // Standard format: a+bi
        $this->assertEquals(new Complex(3, 4), Complex::fromString('3+4i'));
        $this->assertEquals(new Complex(5, -2), Complex::fromString('5-2j'));
        $this->assertEquals(new Complex(-1, 1), Complex::fromString('-1+i'));
        $this->assertEquals(new Complex(2.5, -3.7), Complex::fromString('2.5-3.7I'));

        // With decimals and scientific notation
        $this->assertEquals(new Complex(1.23, 4.56), Complex::fromString('1.23+4.56i'));
        $this->assertEquals(new Complex(-0.5, 2.5e-1), Complex::fromString('-0.5+2.5e-1j'));
        $this->assertEquals(new Complex(123.0, -1), Complex::fromString('123.-I'));
    }

    /**
     * Test parsing complex numbers (imaginary + real)
     */
    public function testFromStringComplexImagFirst(): void
    {
        // Standard format: bi+a
        $this->assertEquals(new Complex(3, 4), Complex::fromString('4i+3'));
        $this->assertEquals(new Complex(5, -2), Complex::fromString('-2j+5'));
        $this->assertEquals(new Complex(-1, 1), Complex::fromString('i-1'));
        $this->assertEquals(new Complex(2.5, -3.7), Complex::fromString('-3.7I+2.5'));

        // With decimals and scientific notation
        $this->assertEquals(new Complex(1.23, 4.56), Complex::fromString('4.56i+1.23'));
        $this->assertEquals(new Complex(-0.5, 2.5e-1), Complex::fromString('2.5e-1j-0.5'));
    }

    /**
     * Test parsing with whitespace (should be stripped)
     */
    public function testFromStringWithWhitespace(): void
    {
        $this->assertEquals(new Complex(3, 4), Complex::fromString(' 3 + 4i '));
        $this->assertEquals(new Complex(5, -2), Complex::fromString('5 - 2j'));
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
            'empty string'                   => [''],
            'random text'                    => ['abc'],
            'incomplete expression'          => ['3+'],
            'double signs'                   => ['++i'],
            'missing imaginary unit'         => ['3+4'],
            'incomplete imaginary'           => ['i+'],
            'wrong imaginary unit'           => ['3+4k'],
            'multiple decimal points'        => ['3.4.5'],
            'incomplete scientific notation' => ['3e'],
            'double e'                       => ['3ee4'],
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
        $this->assertEquals(new Complex(1e5, 2e-3), Complex::fromString('1e5+2e-3j'));
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
            ['-2.5j', 0, -2.5],
            ['3+4i', 3, 4],
            ['5-2j', 5, -2],
            ['-1+i', -1, 1],
            ['4i+3', 3, 4],
            ['-2j+5', 5, -2],
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
}
