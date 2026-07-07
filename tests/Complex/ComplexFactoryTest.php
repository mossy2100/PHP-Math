<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use InvalidArgumentException;
use OceanMoon\Math\Complex;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

use const OceanMoon\Math\I;

#[CoversClass(Complex::class)]
class ComplexFactoryTest extends TestCase
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
     * Test the imaginary unit static method.
     */
    public function testImaginaryUnitMethod(): void
    {
        $i = Complex::i();
        $this->assertSame(0.0, $i->real);
        $this->assertSame(1.0, $i->imaginary);
    }

    /**
     * Test the imaginary unit constant.
     */
    public function testImaginaryUnitConstant(): void
    {
        $this->assertSame(0.0, I->real);
        $this->assertSame(1.0, I->imaginary);
    }

    /**
     * Test toComplex() with an existing Complex instance returns it unchanged (same instance).
     */
    public function testToComplexWithComplexInstance(): void
    {
        $z = new Complex(3, 4);
        $result = Complex::toComplex($z);
        $this->assertSame($z, $result);
    }

    /**
     * Test toComplex() with int and float values.
     */
    public function testToComplexWithNumber(): void
    {
        $result = Complex::toComplex(5);
        $this->assertSame(5.0, $result->real);
        $this->assertSame(0.0, $result->imaginary);

        $result2 = Complex::toComplex(-3.5);
        $this->assertSame(-3.5, $result2->real);
        $this->assertSame(0.0, $result2->imaginary);
    }

    /**
     * Test toComplex() with a parseable string.
     */
    public function testToComplexWithString(): void
    {
        $result = Complex::toComplex('3+4i');
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test toComplex() with a valid 2-element array.
     */
    public function testToComplexWithArray(): void
    {
        $result = Complex::toComplex([3, 4]);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test toComplex() with an array of the wrong length throws.
     */
    public function testToComplexWithArrayInvalidCountThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::toComplex([3, 4, 5]);
    }

    /**
     * Test toComplex() with a non-numeric array element throws.
     */
    public function testToComplexWithArrayNonNumericThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::toComplex([3, 'four']);
    }

    /**
     * Test toComplex() with a valid 2-element Vector.
     *
     * This must be checked before the generic object case, since Vector is itself an object.
     */
    public function testToComplexWithVector(): void
    {
        $vector = Vector::fromArray([3, 4]);
        $result = Complex::toComplex($vector);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test toComplex() with a Vector of the wrong size throws.
     */
    public function testToComplexWithVectorInvalidSizeThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::toComplex(Vector::fromArray([1, 2, 3]));
    }

    /**
     * Test toComplex() with a plain object with "real" and "imaginary" properties.
     */
    public function testToComplexWithObject(): void
    {
        $obj = new stdClass();
        $obj->real = 3;
        $obj->imaginary = 4;
        $result = Complex::toComplex($obj);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test toComplex() with an object missing the required properties throws.
     */
    public function testToComplexWithObjectMissingPropertiesThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::toComplex(new stdClass());
    }

    /**
     * Test toComplex() with an object whose properties are non-numeric throws.
     */
    public function testToComplexWithObjectNonNumericPropertiesThrows(): void
    {
        $obj = new stdClass();
        $obj->real = 'three';
        $obj->imaginary = 4;
        $this->expectException(DomainException::class);
        Complex::toComplex($obj);
    }

    /**
     * Test toComplex() with a value of an unconvertible type throws.
     */
    public function testToComplexWithInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Complex::toComplex(null);
    }

    /**
     * Test toComplex() with a boolean (not a valid conversion source) throws.
     */
    public function testToComplexWithBooleanThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Complex::toComplex(true);
    }

    /**
     * Test fromArray() with a valid 2-element array.
     */
    public function testFromArray(): void
    {
        $result = Complex::fromArray([3, 4]);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test fromArray() with the wrong number of elements throws.
     */
    public function testFromArrayInvalidCountThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromArray([1]);
    }

    /**
     * Test fromArray() with non-numeric elements throws.
     */
    public function testFromArrayNonNumericThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromArray(['a', 'b']);
    }

    /**
     * Test fromObject() with a valid object.
     */
    public function testFromObject(): void
    {
        $obj = new stdClass();
        $obj->real = 3;
        $obj->imaginary = 4;
        $result = Complex::fromObject($obj);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test fromObject() with missing properties throws.
     */
    public function testFromObjectMissingPropertiesThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromObject(new stdClass());
    }

    /**
     * Test fromObject() with non-numeric properties throws.
     */
    public function testFromObjectNonNumericPropertiesThrows(): void
    {
        $obj = new stdClass();
        $obj->real = [];
        $obj->imaginary = 4;
        $this->expectException(DomainException::class);
        Complex::fromObject($obj);
    }

    /**
     * Test fromVector() with a valid 2-element Vector.
     */
    public function testFromVector(): void
    {
        $vector = Vector::fromArray([3, 4]);
        $result = Complex::fromVector($vector);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test fromVector() with the wrong number of elements throws.
     */
    public function testFromVectorInvalidSizeThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromVector(Vector::fromArray([1, 2, 3]));
    }

    /**
     * Test fromVector() with an empty Vector throws.
     */
    public function testFromVectorEmptyThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromVector(new Vector(0));
    }
}
