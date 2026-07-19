<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use DomainException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorElementAccessTest extends TestCase
{
    /**
     * Test get() returns the correct element value.
     */
    public function testGetReturnsCorrectValue(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->assertSame(10.0, $v->get(0));
        $this->assertSame(20.0, $v->get(1));
        $this->assertSame(30.0, $v->get(2));
    }

    /**
     * Test get() with a negative index throws OutOfRangeException.
     */
    public function testGetWithNegativeIndexThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $v->get(-1);
    }

    /**
     * Test get() with an index beyond the last element throws OutOfRangeException.
     */
    public function testGetWithIndexBeyondSizeThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $v->get(3);
    }

    /**
     * Test get() on an empty vector throws OutOfRangeException.
     */
    public function testGetOnEmptyVectorThrows(): void
    {
        $v = new Vector(0);
        $this->expectException(OutOfRangeException::class);
        $v->get(0);
    }

    /**
     * Test set() updates the element value.
     */
    public function testSetUpdatesValue(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $v->set(1, 99);
        $this->assertSame(99.0, $v->get(1));
    }

    /**
     * Test set() casts integer values to float.
     */
    public function testSetCastsIntToFloat(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $v->set(0, 42);
        $this->assertSame(42.0, $v->get(0));
    }

    /**
     * Test set() with a negative index throws OutOfRangeException.
     */
    public function testSetWithNegativeIndexThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $v->set(-1, 5);
    }

    /**
     * Test set() with an index beyond the last element throws OutOfRangeException.
     */
    public function testSetWithIndexBeyondSizeThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $v->set(3, 5);
    }

    /**
     * Test set() on an empty vector throws OutOfRangeException.
     */
    public function testSetOnEmptyVectorThrows(): void
    {
        $v = new Vector(0);
        $this->expectException(OutOfRangeException::class);
        $v->set(0, 5);
    }

    /**
     * Test set() with a non-finite value throws DomainException.
     */
    public function testSetNonFiniteValueThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);

        $this->expectException(DomainException::class);
        $v->set(0, INF);
    }

    /**
     * Test set() with NAN throws DomainException.
     */
    public function testSetNanValueThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);

        $this->expectException(DomainException::class);
        $v->set(0, NAN);
    }

    /**
     * Test set() with negative infinity throws DomainException.
     */
    public function testSetNegativeInfinityValueThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);

        $this->expectException(DomainException::class);
        $v->set(0, -INF);
    }

    /**
     * Test set() does not affect other elements.
     */
    public function testSetDoesNotAffectOtherElements(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $v->set(1, 99);
        $this->assertSame(10.0, $v->get(0));
        $this->assertSame(99.0, $v->get(1));
        $this->assertSame(30.0, $v->get(2));
    }

    /**
     * Test normalize() scales the vector to unit magnitude in place.
     */
    public function testNormalizeScalesInPlace(): void
    {
        $v = Vector::fromArray([3, 4]);
        $v->normalize();

        $this->assertNotNull($v->magnitude);
        $this->assertEqualsWithDelta(1.0, $v->magnitude, EPSILON);
        $this->assertEqualsWithDelta(3.0 / 5.0, $v->get(0), EPSILON);
        $this->assertEqualsWithDelta(4.0 / 5.0, $v->get(1), EPSILON);
    }

    /**
     * Test normalize() on a unit vector leaves it unchanged.
     */
    public function testNormalizeUnitVectorUnchanged(): void
    {
        $v = Vector::fromArray([1, 0, 0]);
        $v->normalize();

        $this->assertNotNull($v->magnitude);
        $this->assertEqualsWithDelta(1.0, $v->magnitude, EPSILON);
        $this->assertSame(1.0, $v->get(0));
        $this->assertSame(0.0, $v->get(1));
        $this->assertSame(0.0, $v->get(2));
    }

    /**
     * Test normalize() on a zero vector throws ArithmeticException and leaves the vector unmodified.
     */
    public function testNormalizeZeroVectorThrows(): void
    {
        $v = new Vector(3);

        try {
            $v->normalize();
            $this->fail('Expected ArithmeticException was not thrown.');
        } catch (ArithmeticException) {
            // Expected. The vector should be left unmodified.
            $this->assertSame([0.0, 0.0, 0.0], $v->toArray());
        }
    }
}
