<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DivisionByZeroError;
use DomainException;
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
        $r = Rational::parse('5');
        $this->assertSame(5, $r->numerator);
        $this->assertSame(1, $r->denominator);

        $r2 = Rational::parse('-123');
        $this->assertSame(-123, $r2->numerator);
        $this->assertSame(1, $r2->denominator);

        $r3 = Rational::parse(' 42 ');
        $this->assertSame(42, $r3->numerator);
        $this->assertSame(1, $r3->denominator);
    }

    /**
     * Test parsing float strings.
     */
    public function testParseFloat(): void
    {
        $r = Rational::parse('0.5');
        $this->assertSame(1, $r->numerator);
        $this->assertSame(2, $r->denominator);

        $r2 = Rational::parse('-0.25');
        $this->assertSame(-1, $r2->numerator);
        $this->assertSame(4, $r2->denominator);

        $r3 = Rational::parse(' 3.14 ');
        // Should convert to some rational approximation
        $this->assertIsInt($r3->numerator); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertIsInt($r3->denominator); // @phpstan-ignore method.alreadyNarrowedType
    }

    /**
     * Test parsing fraction strings.
     */
    public function testParseFraction(): void
    {
        $r = Rational::parse('3/4');
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);

        $r2 = Rational::parse('-5/6');
        $this->assertSame(-5, $r2->numerator);
        $this->assertSame(6, $r2->denominator);

        $r3 = Rational::parse(' 7 / 8 ');
        $this->assertSame(7, $r3->numerator);
        $this->assertSame(8, $r3->denominator);

        // Should reduce
        $r4 = Rational::parse('6/8');
        $this->assertSame(3, $r4->numerator);
        $this->assertSame(4, $r4->denominator);
    }

    /**
     * Test parsing invalid strings throws exception.
     */
    public function testParseInvalidThrows(): void
    {
        $this->expectException(DomainException::class);
        Rational::parse('abc');
    }

    /**
     * Test parsing empty string throws exception.
     */
    public function testParseEmptyThrows(): void
    {
        $this->expectException(DomainException::class);
        Rational::parse('');
    }

    /**
     * Test parsing fraction with zero denominator throws exception.
     */
    public function testParseFractionZeroDenominatorThrows(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Rational::parse('5/0');
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
        $this->expectException(DomainException::class);
        Rational::toRational('invalid');
    }
}
