<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use OceanMoon\Core\Exceptions\ConversionException;
use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalFactoryTest extends TestCase
{
    /**
     * Test parsing integer strings.
     */
    public function testParseInteger(): void
    {
        $r = Rational::fromString('5');
        $this->assertSame(5, $r->numerator);
        $this->assertSame(1, $r->denominator);

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
        $r = Rational::fromString('0.5');
        $this->assertSame(1, $r->numerator);
        $this->assertSame(2, $r->denominator);

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
        $r = Rational::fromString('3/4');
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);

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
     * Test parsing invalid strings throws exception.
     */
    public function testParseInvalidThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::fromString('abc');
    }

    /**
     * Test parsing empty string throws exception.
     */
    public function testParseEmptyThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::fromString('');
    }

    /**
     * Test parsing fraction with zero denominator throws exception.
     */
    public function testParseFractionZeroDenominatorThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::fromString('5/0');
    }

    /**
     * Test toRational with Rational argument.
     */
    public function testToRationalWithRational(): void
    {
        $r = new Rational(3, 4);
        $r2 = Rational::toRational($r);

        $this->assertSame($r, $r2); // Should return same instance
    }

    /**
     * Test toRational with integer argument.
     */
    public function testToRationalWithInteger(): void
    {
        $r = Rational::toRational(5);
        $this->assertSame(5, $r->numerator);
        $this->assertSame(1, $r->denominator);
    }

    /**
     * Test toRational with float argument.
     */
    public function testToRationalWithFloat(): void
    {
        $r = Rational::toRational(0.5);
        $this->assertSame(1, $r->numerator);
        $this->assertSame(2, $r->denominator);
    }

    /**
     * Test toRational with string argument.
     */
    public function testToRationalWithString(): void
    {
        $r = Rational::toRational('3/4');
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    /**
     * Test toRational with invalid string throws exception.
     */
    public function testToRationalWithInvalidStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::toRational('invalid');
    }

    /**
     * Test toRational with a value of an unconvertible type throws ConversionException.
     */
    public function testToRationalWithInvalidTypeThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::toRational(null);
    }

    /**
     * Test toRational with a boolean (not a valid conversion source) throws.
     */
    public function testToRationalWithBooleanThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::toRational(true);
    }

    /**
     * Test fromFloat with a whole-number float converts directly and exactly.
     */
    public function testFromFloatWholeNumber(): void
    {
        $r = Rational::fromFloat(3.0);
        $this->assertSame(3, $r->numerator);
        $this->assertSame(1, $r->denominator);

        $r2 = Rational::fromFloat(1000000.0);
        $this->assertSame(1000000, $r2->numerator);
        $this->assertSame(1, $r2->denominator);
    }

    /**
     * Test fromFloat with values needing continued-fraction approximation.
     */
    public function testFromFloatApproximation(): void
    {
        $r = Rational::fromFloat(0.5);
        $this->assertSame(1, $r->numerator);
        $this->assertSame(2, $r->denominator);

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
        $r = Rational::fromFloat(M_PI);
        $this->assertEqualsWithDelta(M_PI, $r->numerator / $r->denominator, EPSILON);

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
        $r = Rational::fromFloat(1.0 / PHP_INT_MAX);
        $this->assertSame(1, $r->numerator);
        $this->assertSame(PHP_INT_MAX, $r->denominator);

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
     * Test fromFloat with a very small value throws ConversionException.
     */
    public function testFromFloatVerySmallThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::fromFloat(1e-20);
    }

    /**
     * Test fromFloat with a very large value throws ConversionException.
     */
    public function testFromFloatVeryLargeThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::fromFloat((float) PHP_INT_MAX * 2);
    }

    /**
     * Test fromFloat with a value that triggers convergent overflow in continued fractions.
     */
    public function testFromFloatConvergentOverflow(): void
    {
        $value = 2.1213650134300899e-10;
        $r = Rational::fromFloat($value);

        $this->assertSame(431, $r->numerator);
        $this->assertSame(2031710701701, $r->denominator);
        $this->assertEqualsWithDelta($value, $r->numerator / $r->denominator, 1e-15);
    }

    /**
     * Test fromFloat with a value that triggers zero remainder in continued fractions.
     */
    public function testFromFloatZeroRemainder(): void
    {
        $value = 2.176543618258578e-17;
        $r = Rational::fromFloat($value);

        $this->assertSame(1, $r->numerator);
        $this->assertSame(45944404312011256, $r->denominator);
        $this->assertEqualsWithDelta($value, $r->numerator / $r->denominator, 1e-25);
    }

    /**
     * Test fromFloat with infinite or NAN values throws ConversionException.
     */
    public function testFromFloatNonFiniteThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::fromFloat(INF);
    }

    /**
     * Test fromFloat with NAN throws ConversionException.
     */
    public function testFromFloatNanThrows(): void
    {
        $this->expectException(ConversionException::class);
        Rational::fromFloat(NAN);
    }
}
