<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use DivisionByZeroError;
use LengthException;
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorArithmeticTest extends TestCase
{
    use FloatAssertions;

    /**
     * Test negating a vector.
     */
    public function testNeg(): void
    {
        $v = Vector::fromArray([1, -2, 3]);
        $result = $v->neg();
        $this->assertSame([-1.0, 2.0, -3.0], $result->toArray());
    }

    /**
     * Test negating a zero vector returns a zero vector.
     */
    public function testNegZeroVector(): void
    {
        $v = new Vector(3);
        $result = $v->neg();
        $this->assertSame([0.0, 0.0, 0.0], $result->toArray());
    }

    /**
     * Test adding two vectors.
     */
    public function testAdd(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([4, 5, 6]);
        $result = $a->add($b);
        $this->assertSame([5.0, 7.0, 9.0], $result->toArray());
    }

    /**
     * Test adding vectors with different sizes throws LengthException.
     */
    public function testAddWithDifferentSizesThrows(): void
    {
        $a = Vector::fromArray([1, 2]);
        $b = Vector::fromArray([1, 2, 3]);
        $this->expectException(LengthException::class);
        $a->add($b);
    }

    /**
     * Test subtracting two vectors.
     */
    public function testSub(): void
    {
        $a = Vector::fromArray([10, 20, 30]);
        $b = Vector::fromArray([1, 2, 3]);
        $result = $a->sub($b);
        $this->assertSame([9.0, 18.0, 27.0], $result->toArray());
    }

    /**
     * Test subtracting vectors with different sizes throws LengthException.
     */
    public function testSubWithDifferentSizesThrows(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2]);
        $this->expectException(LengthException::class);
        $a->sub($b);
    }

    /**
     * Test multiplying a vector by an integer scalar.
     */
    public function testMulByInt(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $result = $v->mul(3);
        $this->assertSame([3.0, 6.0, 9.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by a float scalar.
     */
    public function testMulByFloat(): void
    {
        $v = Vector::fromArray([2, 4, 6]);
        $result = $v->mul(0.5);
        $this->assertSame([1.0, 2.0, 3.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by zero.
     */
    public function testMulByZero(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $result = $v->mul(0);
        $this->assertSame([0.0, 0.0, 0.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by a negative scalar.
     */
    public function testMulByNegative(): void
    {
        $v = Vector::fromArray([1, -2, 3]);
        $result = $v->mul(-2);
        $this->assertSame([-2.0, 4.0, -6.0], $result->toArray());
    }

    /**
     * Test dividing a vector by a scalar.
     */
    public function testDiv(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $result = $v->div(10);
        $this->assertEqualsWithDelta([1.0, 2.0, 3.0], $result->toArray(), 1e-10);
    }

    /**
     * Test dividing a vector by zero throws DivisionByZeroError.
     */
    public function testDivByZeroThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->expectException(DivisionByZeroError::class);
        $v->div(0);
    }

    /**
     * Test dot product of two vectors.
     */
    public function testDot(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([4, 5, 6]);
        // 1*4 + 2*5 + 3*6 = 4 + 10 + 18 = 32
        $this->assertSame(32.0, $a->dot($b));
    }

    /**
     * Test dot product with different sizes throws LengthException.
     */
    public function testDotWithDifferentSizesThrows(): void
    {
        $a = Vector::fromArray([1, 2]);
        $b = Vector::fromArray([1, 2, 3]);
        $this->expectException(LengthException::class);
        $a->dot($b);
    }

    /**
     * Test cross product of unit vectors i x j = k.
     */
    public function testCrossUnitVectors(): void
    {
        $i = Vector::fromArray([1, 0, 0]);
        $j = Vector::fromArray([0, 1, 0]);
        $result = $i->cross($j);
        // i × j = k => [0, 0, 1]
        $this->assertSame([0.0, 0.0, 1.0], $result->toArray());
    }

    /**
     * Test cross product of two 3D vectors with a known result.
     */
    public function testCrossKnownResult(): void
    {
        $a = Vector::fromArray([2, 3, 4]);
        $b = Vector::fromArray([5, 6, 7]);
        $result = $a->cross($b);
        // (3*7 - 4*6, 4*5 - 2*7, 2*6 - 3*5) = (21-24, 20-14, 12-15) = (-3, 6, -3)
        $this->assertSame([-3.0, 6.0, -3.0], $result->toArray());
    }

    /**
     * Test cross product with first vector not size 3 throws LengthException.
     */
    public function testCrossWithFirstVectorNotSize3Throws(): void
    {
        $a = Vector::fromArray([1, 2]);
        $b = Vector::fromArray([3, 4, 5]);
        $this->expectException(LengthException::class);
        $a->cross($b);
    }

    /**
     * Test cross product with second vector not size 3 throws LengthException.
     */
    public function testCrossWithSecondVectorNotSize3Throws(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([4, 5]);
        $this->expectException(LengthException::class);
        $a->cross($b);
    }

    // endregion

    // region normalize() tests

    /**
     * Test normalize produces a unit vector.
     */
    public function testNormalizeProducesUnitVector(): void
    {
        $v = Vector::fromArray([3, 4]);
        $unit = $v->normalize();

        $this->assertNotNull($unit->magnitude);
        $this->assertApproxEqual(1.0, $unit->magnitude);
        $this->assertApproxEqual(3.0 / 5.0, $unit->get(0));
        $this->assertApproxEqual(4.0 / 5.0, $unit->get(1));
    }

    /**
     * Test normalize on a unit vector returns equivalent vector.
     */
    public function testNormalizeUnitVector(): void
    {
        $v = Vector::fromArray([1, 0, 0]);
        $unit = $v->normalize();

        $this->assertNotNull($unit->magnitude);
        $this->assertApproxEqual(1.0, $unit->magnitude);
        $this->assertSame(1.0, $unit->get(0));
        $this->assertSame(0.0, $unit->get(1));
        $this->assertSame(0.0, $unit->get(2));
    }

    /**
     * Test normalize on zero vector throws DivisionByZeroError.
     */
    public function testNormalizeZeroVectorThrows(): void
    {
        $v = new Vector(3);
        $this->expectException(DivisionByZeroError::class);
        $v->normalize();
    }

    // endregion
}
