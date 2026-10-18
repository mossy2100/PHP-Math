<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DomainException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Exceptions\FormatException;
use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalFactoryTest extends TestCase
{
    #region Method fromString() tests.

    /**
     * Test parsing integer strings.
     */
    public function testParseInteger(): void
    {
        $r1 = Rational::fromString('5');
        $this->assertSame(5, $r1->numerator);
        $this->assertSame(1, $r1->denominator);

        $r2 = Rational::fromString('-123');
        $this->assertSame(-123, $r2->numerator);
        $this->assertSame(1, $r2->denominator);

        $r3 = Rational::fromString(' 42 ');
        $this->assertSame(42, $r3->numerator);
        $this->assertSame(1, $r3->denominator);
    }

    /**
     * Test parsing float strings.
     */
    public function testParseFloat(): void
    {
        $r1 = Rational::fromString('0.5');
        $this->assertSame(1, $r1->numerator);
        $this->assertSame(2, $r1->denominator);

        $r2 = Rational::fromString('-0.25');
        $this->assertSame(-1, $r2->numerator);
        $this->assertSame(4, $r2->denominator);

        $r3 = Rational::fromString(' 3.14 ');
        // Should convert to some rational approximation
        $this->assertIsInt($r3->numerator); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertIsInt($r3->denominator); // @phpstan-ignore method.alreadyNarrowedType
    }

    /**
     * Test parsing fraction strings.
     */
    public function testParseFraction(): void
    {
        $r1 = Rational::fromString('3/4');
        $this->assertSame(3, $r1->numerator);
        $this->assertSame(4, $r1->denominator);

        $r2 = Rational::fromString('-5/6');
        $this->assertSame(-5, $r2->numerator);
        $this->assertSame(6, $r2->denominator);

        $r3 = Rational::fromString(' 7 / 8 ');
        $this->assertSame(7, $r3->numerator);
        $this->assertSame(8, $r3->denominator);

        // Should reduce
        $r4 = Rational::fromString('6/8');
        $this->assertSame(3, $r4->numerator);
        $this->assertSame(4, $r4->denominator);
    }

    /**
     * Test parsing invalid strings throws FormatException.
     */
    public function testParseInvalidThrows(): void
    {
        $this->expectException(FormatException::class);
        Rational::fromString('abc');
    }

    /**
     * Test parsing empty string throws FormatException.
     */
    public function testParseEmptyThrows(): void
    {
        $this->expectException(FormatException::class);
        Rational::fromString('');
    }

    /**
     * Test parsing fraction with zero denominator throws ArithmeticException.
     */
    public function testParseFractionZeroDenominatorThrows(): void
    {
        $this->expectException(ArithmeticException::class);
        Rational::fromString('5/0');
    }

    /**
     * Test the invalid-format message includes the string, quoted and escaped.
     */
    public function testFromStringInvalidMessageIncludesString(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage("Cannot convert string 'it\\'s 3x' to Rational. Invalid format.");
        Rational::fromString("it's 3x");
    }

    /**
     * Test the invalid-format message abbreviates a long string to 32 characters.
     */
    public function testFromStringInvalidMessageAbbreviatesLongString(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage(
            "Cannot convert string '" . str_repeat('x', 29) . "…' to Rational. Invalid format."
        );
        Rational::fromString(str_repeat('x', 40));
    }

    /**
     * Test the invalid-format message leaves out a string that isn't valid UTF-8.
     */
    public function testFromStringInvalidUtf8MessageOmitsString(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('Cannot convert string to Rational. Invalid format.');
        Rational::fromString("\xff\xfe");
    }

    /**
     * Data provider for testFromStringOutOfRangeThrows(): numeric strings outside the valid range for a Rational.
     *
     * @return array<string, array{string}>
     */
    public static function fromStringOutOfRangeProvider(): array
    {
        return [
            'PHP_INT_MAX + 1'       => ['9223372036854775808'],
            'PHP_INT_MIN - 1'       => ['-9223372036854775809'],
            '10^19 with a sign'     => ['+10000000000000000000'],
            'leading zeros'         => ['009223372036854775808'],
            'float too large'       => ['9.3e18'],
            'float overflows'       => ['1e400'],
            'float overflows, neg.' => ['-1e999'],
            'float too small'       => ['1e-30'],
        ];
    }

    /**
     * Test fromString() throws DomainException, naming the string, for a number outside the valid range, rather than
     * clamping an integer to PHP_INT_MAX, or calling a huge exponent an invalid format.
     */
    #[DataProvider('fromStringOutOfRangeProvider')]
    public function testFromStringOutOfRangeThrows(string $str): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Cannot convert string '$str' to Rational. Outside valid range.");
        Rational::fromString($str);
    }

    /**
     * Test fromString() at the edges of what it accepts: PHP_INT_MAX, with or without leading zeros, and zero written
     * with an exponent, which filter_var() rejects as a float.
     */
    public function testFromStringEdgeValues(): void
    {
        $this->assertSame(PHP_INT_MAX, Rational::fromString('9223372036854775807')->numerator);
        $this->assertSame(PHP_INT_MAX, Rational::fromString('0009223372036854775807')->numerator);
        $this->assertSame(-PHP_INT_MAX, Rational::fromString('-9223372036854775807')->numerator);
        $this->assertSame(0, Rational::fromString('0e9')->numerator);
    }

    #endregion

    #region Method fromFloat() tests.

    /**
     * Test fromFloat with a whole-number float converts directly and exactly.
     */
    public function testFromFloatWholeNumber(): void
    {
        $r1 = Rational::fromFloat(3.0);
        $this->assertSame(3, $r1->numerator);
        $this->assertSame(1, $r1->denominator);

        $r2 = Rational::fromFloat(1000000.0);
        $this->assertSame(1000000, $r2->numerator);
        $this->assertSame(1, $r2->denominator);
    }

    /**
     * Test fromFloat with values needing continued-fraction approximation.
     */
    public function testFromFloatApproximation(): void
    {
        $r1 = Rational::fromFloat(0.5);
        $this->assertSame(1, $r1->numerator);
        $this->assertSame(2, $r1->denominator);

        $r2 = Rational::fromFloat(0.25);
        $this->assertSame(1, $r2->numerator);
        $this->assertSame(4, $r2->denominator);

        // 0.333... should approximate to 1/3.
        $r3 = Rational::fromFloat(1 / 3);
        $this->assertSame(1, $r3->numerator);
        $this->assertSame(3, $r3->denominator);

        $r4 = Rational::fromFloat(-0.5);
        $this->assertSame(-1, $r4->numerator);
        $this->assertSame(2, $r4->denominator);

        $r5 = Rational::fromFloat(-0.75);
        $this->assertSame(-3, $r5->numerator);
        $this->assertSame(4, $r5->denominator);
    }

    /**
     * Test fromFloat approximates irrational numbers as closely as possible.
     */
    public function testFromFloatIrrational(): void
    {
        $r1 = Rational::fromFloat(M_PI);
        $this->assertEqualsWithDelta(M_PI, $r1->numerator / $r1->denominator, EPSILON);

        $r2 = Rational::fromFloat(M_E);
        $this->assertEqualsWithDelta(M_E, $r2->numerator / $r2->denominator, EPSILON);
    }

    /**
     * Test fromFloat with (float)PHP_INT_MAX.
     */
    public function testFromFloatPhpIntMax(): void
    {
        $r = Rational::fromFloat((float) PHP_INT_MAX);
        $this->assertSame(PHP_INT_MAX, $r->numerator);
        $this->assertSame(1, $r->denominator);
    }

    /**
     * Test fromFloat with (float)PHP_INT_MIN.
     *
     * When we convert PHP_INT_MIN to a float, and call fromFloat() it correctly finds a Rational with numerator
     * -PHP_INT_MAX and denominator 1.
     *
     * This is because abs((float)PHP_INT_MIN) equals abs((float)PHP_INT_MAX) on 64-bit systems, because most values
     * greater than 2^53 cannot be represented exactly as floats.
     *
     * Therefore, this test might fail on 32-bit systems, where PHP_INT_MIN is -2^31 and PHP_INT_MAX is 2^31-1, and
     * abs((float)PHP_INT_MIN) is not equal to abs((float)PHP_INT_MAX). I could code for this, but it would be hard to
     * test without a 32-bit system, so I will leave it as is for now.
     */
    public function testFromFloatPhpIntMin(): void
    {
        $r = Rational::fromFloat((float) PHP_INT_MIN);
        $this->assertSame(-PHP_INT_MAX, $r->numerator);
        $this->assertSame(1, $r->denominator);
    }

    /**
     * Test fromFloat with 1/PHP_INT_MAX and -1/PHP_INT_MAX.
     */
    public function testFromFloatInversePhpIntMax(): void
    {
        $r1 = Rational::fromFloat(1.0 / PHP_INT_MAX);
        $this->assertSame(1, $r1->numerator);
        $this->assertSame(PHP_INT_MAX, $r1->denominator);

        $r2 = Rational::fromFloat(-1.0 / PHP_INT_MAX);
        $this->assertSame(-1, $r2->numerator);
        $this->assertSame(PHP_INT_MAX, $r2->denominator);
    }

    /**
     * Test fromFloat with 1/PHP_INT_MIN.
     */
    public function testFromFloatInversePhpIntMin(): void
    {
        $r = Rational::fromFloat(1.0 / PHP_INT_MIN);
        $this->assertSame(-1, $r->numerator);
        $this->assertSame(PHP_INT_MAX, $r->denominator);
    }

    /**
     * Test fromFloat with a very small value throws DomainException.
     */
    public function testFromFloatVerySmallThrows(): void
    {
        $this->expectException(DomainException::class);
        Rational::fromFloat(1e-20);
    }

    /**
     * Test fromFloat with a very large value throws DomainException.
     */
    public function testFromFloatVeryLargeThrows(): void
    {
        $this->expectException(DomainException::class);
        Rational::fromFloat((float) PHP_INT_MAX * 2);
    }

    /**
     * Test fromFloat with a value that triggers convergent overflow in continued fractions.
     */
    public function testFromFloatConvergentOverflow(): void
    {
        $f = 2.1213650134300899e-10;
        $r = Rational::fromFloat($f);

        $this->assertSame(431, $r->numerator);
        $this->assertSame(2031710701701, $r->denominator);
        $this->assertEqualsWithDelta($f, $r->numerator / $r->denominator, 1e-15);
    }

    /**
     * Test fromFloat with a value that triggers zero remainder in continued fractions.
     */
    public function testFromFloatZeroRemainder(): void
    {
        $f = 2.176543618258578e-17;
        $r = Rational::fromFloat($f);

        $this->assertSame(1, $r->numerator);
        $this->assertSame(45944404312011256, $r->denominator);
        $this->assertEqualsWithDelta($f, $r->numerator / $r->denominator, 1e-25);
    }

    /**
     * Test fromFloat with infinite or NAN values throws DomainException.
     */
    public function testFromFloatNonFiniteThrows(): void
    {
        $this->expectException(DomainException::class);
        Rational::fromFloat(INF);
    }

    /**
     * Test fromFloat with NAN throws DomainException.
     */
    public function testFromFloatNanThrows(): void
    {
        $this->expectException(DomainException::class);
        Rational::fromFloat(NAN);
    }

    #endregion
}
