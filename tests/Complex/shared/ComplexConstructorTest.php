<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Complex::class)]
class ComplexConstructorTest extends TestCase
{
    /**
     * Test the constructor with various inputs.
     */
    public function testConstructor(): void
    {
        $z1 = new Complex(3, 4);
        $this->assertSame(3.0, $z1->real);
        $this->assertSame(4.0, $z1->imaginary);

        $z2 = new Complex(-5.5, 2.3);
        $this->assertSame(-5.5, $z2->real);
        $this->assertSame(2.3, $z2->imaginary);

        $z3 = new Complex();
        $this->assertSame(0.0, $z3->real);
        $this->assertSame(0.0, $z3->imaginary);

        $z4 = new Complex(5);
        $this->assertSame(5.0, $z4->real);
        $this->assertSame(0.0, $z4->imaginary);
    }

    /**
     * Test the constructor accepts int or float.
     */
    public function testConstructorIntFloat(): void
    {
        $z1 = new Complex(3, 4.5);
        $this->assertSame(3.0, $z1->real);
        $this->assertSame(4.5, $z1->imaginary);

        $z2 = new Complex(3.5, 4);
        $this->assertSame(3.5, $z2->real);
        $this->assertSame(4.0, $z2->imaginary);
    }

    /**
     * Test constructor throws for INF real part.
     */
    public function testConstructorInfRealThrows(): void
    {
        $this->expectException(DomainException::class);
        new Complex(INF, 0);
    }

    /**
     * Test constructor throws for INF imaginary part.
     */
    public function testConstructorInfImaginaryThrows(): void
    {
        $this->expectException(DomainException::class);
        new Complex(0, INF);
    }

    /**
     * Test constructor throws for NAN real part.
     */
    public function testConstructorNanRealThrows(): void
    {
        $this->expectException(DomainException::class);
        new Complex(NAN, 0);
    }

    /**
     * Test constructor throws for NAN imaginary part.
     */
    public function testConstructorNanImaginaryThrows(): void
    {
        $this->expectException(DomainException::class);
        new Complex(0, NAN);
    }

    /**
     * Test constructor throws for negative infinity.
     */
    public function testConstructorNegativeInfThrows(): void
    {
        $this->expectException(DomainException::class);
        new Complex(-INF, -INF);
    }

    /**
     * Test constructor throws TypeError for non-numeric inputs.
     */
    public function testConstructorNonFloatThrows(): void
    {
        $this->expectException(TypeError::class);
        new Complex('3', '4'); // @phpstan-ignore argument.type, argument.type
    }
}
