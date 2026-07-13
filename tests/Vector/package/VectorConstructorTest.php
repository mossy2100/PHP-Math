<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use DomainException;
use OceanMoon\Core\Exceptions\ConversionException;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorConstructorTest extends TestCase
{
    /**
     * Test constructor with size zero creates an empty vector.
     */
    public function testConstructorWithSizeZero(): void
    {
        $v = new Vector(0);
        $this->assertSame(0, $v->size);
    }

    /**
     * Test constructor with size one creates a single-element vector.
     */
    public function testConstructorWithSizeOne(): void
    {
        $v = new Vector(1);
        $this->assertSame(1, $v->size);
    }

    /**
     * Test constructor with size five creates a five-element vector.
     */
    public function testConstructorWithSizeFive(): void
    {
        $v = new Vector(5);
        $this->assertSame(5, $v->size);
    }

    /**
     * Test constructor with negative size throws DomainException.
     */
    public function testConstructorWithNegativeSizeThrows(): void
    {
        $this->expectException(DomainException::class);
        new Vector(-1);
    }

    /**
     * Test constructor initialises all elements to zero.
     */
    public function testConstructorInitialisesElementsToZero(): void
    {
        $v = new Vector(3);
        $this->assertSame([0.0, 0.0, 0.0], $v->toArray());
    }

    /**
     * Test count() matches size for a non-empty vector.
     */
    public function testCount(): void
    {
        $v = new Vector(5);
        $this->assertSame(5, $v->count());
        $this->assertSame($v->size, $v->count());
    }

    /**
     * Test count() with an empty vector.
     */
    public function testCountWithEmptyVector(): void
    {
        $v = new Vector(0);
        $this->assertSame(0, $v->count());
    }

    /**
     * Test the global count() function works via the Countable interface.
     */
    public function testGlobalCountFunction(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->assertCount(3, $v);
        $this->assertSame(3, count($v));
    }

    /**
     * Test fromArray with integer values.
     */
    public function testFromArrayWithInts(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->assertSame([1.0, 2.0, 3.0], $v->toArray());
        $this->assertSame(3, $v->size);
    }

    /**
     * Test fromArray with float values.
     */
    public function testFromArrayWithFloats(): void
    {
        $v = Vector::fromArray([1.5, 2.5, 3.5]);
        $this->assertSame([1.5, 2.5, 3.5], $v->toArray());
    }

    /**
     * Test fromArray with mixed int and float values.
     */
    public function testFromArrayWithMixed(): void
    {
        $v = Vector::fromArray([1, 2.5, 3]);
        $this->assertSame([1.0, 2.5, 3.0], $v->toArray());
    }

    /**
     * Test fromArray with empty array creates a size-zero vector.
     */
    public function testFromArrayWithEmptyArray(): void
    {
        $v = Vector::fromArray([]);
        $this->assertSame(0, $v->size);
        $this->assertSame([], $v->toArray());
    }

    /**
     * Test fromArray with non-numeric values throws ConversionException.
     */
    public function testFromArrayWithNonNumericThrows(): void
    {
        $this->expectException(ConversionException::class);
        Vector::fromArray([1, 'hello', 3]);
    }

    /**
     * Test fromArray with a non-sequential (non-list) array throws ConversionException.
     */
    public function testFromArrayWithNonSequentialArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        Vector::fromArray([
            5  => 10,
            10 => 20,
            15 => 30,
        ]);
    }

    /**
     * Test magnitude of a [3, 4] vector equals 5.
     */
    public function testMagnitudeThreeFourEqualsFive(): void
    {
        $v = Vector::fromArray([3, 4]);
        $this->assertSame(5.0, $v->magnitude);
    }

    /**
     * Test magnitude of an empty vector equals zero.
     */
    public function testMagnitudeEmptyVectorEqualsZero(): void
    {
        $v = new Vector(0);
        $this->assertSame(0.0, $v->magnitude);
    }

    /**
     * Test magnitude of a single-element vector equals the absolute value of the element.
     */
    public function testMagnitudeSingleElement(): void
    {
        $v = Vector::fromArray([1]);
        $this->assertSame(1.0, $v->magnitude);
    }
}
