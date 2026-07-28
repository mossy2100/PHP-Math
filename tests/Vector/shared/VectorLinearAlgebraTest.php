<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use LengthException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorLinearAlgebraTest extends TestCase
{
    #region Method dot() tests.

    /**
     * Test dot product of two vectors.
     */
    public function testDot(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([4, 5, 6]);
        // 1*4 + 2*5 + 3*6 = 4 + 10 + 18 = 32
        $this->assertSame(32.0, $v1->dot($v2));
    }

    /**
     * Test dot product with different sizes throws LengthException.
     */
    public function testDotWithDifferentSizesThrows(): void
    {
        $v1 = Vector::fromArray([1, 2]);
        $v2 = Vector::fromArray([1, 2, 3]);
        $this->expectException(LengthException::class);
        $v1->dot($v2);
    }

    #endregion

    #region Method cross() tests.

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
        $v1 = Vector::fromArray([2, 3, 4]);
        $v2 = Vector::fromArray([5, 6, 7]);
        $result = $v1->cross($v2);
        // (3*7 - 4*6, 4*5 - 2*7, 2*6 - 3*5) = (21-24, 20-14, 12-15) = (-3, 6, -3)
        $this->assertSame([-3.0, 6.0, -3.0], $result->toArray());
    }

    /**
     * Test cross product with first vector not size 3 throws LengthException.
     */
    public function testCrossWithFirstVectorNotSize3Throws(): void
    {
        $v1 = Vector::fromArray([1, 2]);
        $v2 = Vector::fromArray([3, 4, 5]);
        $this->expectException(LengthException::class);
        $v1->cross($v2);
    }

    /**
     * Test cross product with second vector not size 3 throws LengthException.
     */
    public function testCrossWithSecondVectorNotSize3Throws(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([4, 5]);
        $this->expectException(LengthException::class);
        $v1->cross($v2);
    }

    #endregion

    #region Method outer() tests.

    /**
     * Test outer product of two same-size vectors.
     */
    public function testOuter(): void
    {
        $v1 = Vector::fromArray([1, 2]);
        $v2 = Vector::fromArray([3, 4]);
        $result = $v1->outer($v2);

        $this->assertInstanceOf(Matrix::class, $result);
        $this->assertSame(2, $result->rowCount);
        $this->assertSame(2, $result->columnCount);
        // [1,2] ⊗ [3,4] = [[1*3, 1*4], [2*3, 2*4]] = [[3, 4], [6, 8]]
        $this->assertSame([
            [3.0, 4.0],
            [6.0, 8.0],
        ], $result->toArray());
    }

    /**
     * Test outer product of two different-size vectors. Unlike dot() and cross(), outer() doesn't
     * require the vectors to be the same size.
     */
    public function testOuterWithDifferentSizes(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([4, 5]);
        $result = $v1->outer($v2);

        $this->assertSame(3, $result->rowCount);
        $this->assertSame(2, $result->columnCount);
        // [1,2,3] ⊗ [4,5] = [[4, 5], [8, 10], [12, 15]]
        $this->assertSame([
            [4.0, 5.0],
            [8.0, 10.0],
            [12.0, 15.0],
        ], $result->toArray());
    }

    #endregion

    #region Method normalized() tests.

    /**
     * Test normalized produces a unit vector.
     */
    public function testNormalizedProducesUnitVector(): void
    {
        $v = Vector::fromArray([3, 4]);
        $unit = $v->normalized();

        $this->assertEqualsWithDelta(1.0, $unit->magnitude, EPSILON);
        $this->assertEqualsWithDelta(3.0 / 5.0, $unit->get(0), EPSILON);
        $this->assertEqualsWithDelta(4.0 / 5.0, $unit->get(1), EPSILON);
    }

    /**
     * Test normalized on a unit vector returns equivalent vector.
     */
    public function testNormalizedUnitVector(): void
    {
        $v = Vector::fromArray([1, 0, 0]);
        $unit = $v->normalized();

        $this->assertEqualsWithDelta(1.0, $unit->magnitude, EPSILON);
        $this->assertSame(1.0, $unit->get(0));
        $this->assertSame(0.0, $unit->get(1));
        $this->assertSame(0.0, $unit->get(2));
    }

    /**
     * Test normalized on zero vector throws ArithmeticException.
     */
    public function testNormalizedZeroVectorThrows(): void
    {
        $v = new Vector(3);
        $this->expectException(ArithmeticException::class);
        $v->normalized();
    }

    #endregion
}
