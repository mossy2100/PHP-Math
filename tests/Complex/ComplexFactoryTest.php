<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use InvalidArgumentException;
use LengthException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

use const OceanMoon\Math\I;

#[CoversClass(Complex::class)]
class ComplexFactoryTest extends TestCase
{
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
        $this->expectException(LengthException::class);
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
     * Test fromArray() with a valid 2-element list.
     */
    public function testFromArray(): void
    {
        $result = Complex::fromArray([3, 4]);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test fromArray() with the result of a cast.
     */
    public function testFromArrayWithCast(): void
    {
        $z = new Complex(3, 4);
        $a = (array) $z;
        $result = Complex::fromArray($a);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test fromArray() with a list containing the wrong number of elements throws.
     */
    public function testFromArrayInvalidCountThrows(): void
    {
        $this->expectException(LengthException::class);
        Complex::fromArray([1]);
    }

    /**
     * Test fromArray() with a list containing non-numeric elements throws.
     */
    public function testFromArrayNonNumericThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromArray(['a', 'b']);
    }

    /**
     * Test fromArray() with a valid associative array.
     */
    public function testFromArrayAssociative(): void
    {
        // Key order shouldn't matter.
        $result = Complex::fromArray([
            'imaginary' => 4,
            'real'      => 3,
        ]);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test fromArray() supports the result of (array) $complex, ignoring extra keys.
     */
    public function testFromArrayAssociativeIgnoresExtraKeys(): void
    {
        $result = Complex::fromArray([
            'real'      => 3,
            'imaginary' => 4,
            'magnitude' => 5,
            'phase'     => 0.9,
        ]);
        $this->assertSame(3.0, $result->real);
        $this->assertSame(4.0, $result->imaginary);
    }

    /**
     * Test fromArray() with an associative array missing the "real" key throws.
     */
    public function testFromArrayAssociativeMissingRealKeyThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromArray([
            'imaginary' => 4,
        ]);
    }

    /**
     * Test fromArray() with an associative array missing the "imaginary" key throws.
     */
    public function testFromArrayAssociativeMissingImaginaryKeyThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromArray([
            'real' => 3,
        ]);
    }

    /**
     * Test fromArray() with an associative array containing a non-numeric "real" value throws.
     */
    public function testFromArrayAssociativeNonNumericRealThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromArray([
            'real'      => 'a',
            'imaginary' => 4,
        ]);
    }

    /**
     * Test fromArray() with an associative array containing a non-numeric "imaginary" value throws.
     */
    public function testFromArrayAssociativeNonNumericImaginaryThrows(): void
    {
        $this->expectException(DomainException::class);
        Complex::fromArray([
            'real'      => 3,
            'imaginary' => 'b',
        ]);
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
     * Test fromObject() with a missing "real" property throws.
     */
    public function testFromObjectMissingRealPropertyThrows(): void
    {
        $obj = new stdClass();
        $obj->imaginary = 4;
        $this->expectException(DomainException::class);
        Complex::fromObject($obj);
    }

    /**
     * Test fromObject() with a missing "imaginary" property throws.
     */
    public function testFromObjectMissingImaginaryPropertyThrows(): void
    {
        $obj = new stdClass();
        $obj->real = 3;
        $this->expectException(DomainException::class);
        Complex::fromObject($obj);
    }

    /**
     * Test fromObject() with a non-numeric "real" property throws.
     */
    public function testFromObjectNonNumericRealPropertyThrows(): void
    {
        $obj = new stdClass();
        $obj->real = [];
        $obj->imaginary = 4;
        $this->expectException(DomainException::class);
        Complex::fromObject($obj);
    }

    /**
     * Test fromObject() with a non-numeric "imaginary" property throws.
     */
    public function testFromObjectNonNumericImaginaryPropertyThrows(): void
    {
        $obj = new stdClass();
        $obj->real = 3;
        $obj->imaginary = [];
        $this->expectException(DomainException::class);
        Complex::fromObject($obj);
    }
}
