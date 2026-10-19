<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use DomainException;
use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorModificationTest extends TestCase
{
    #region Method set() tests.

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
     * Test set() and array-style assignment store negative zeros as positive zeros.
     */
    public function testSetNormalizesNegativeZero(): void
    {
        $v = Vector::fromArray([1, 2]);
        $v->set(0, -0.0);
        $v[1] = -0.0;
        $this->assertSame(['0', '0'], array_map(strval(...), $v->toArray()));
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

    #endregion
}
