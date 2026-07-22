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
    #region Method mulMatrix() tests.

    /**
     * Test multiplying a vector by a Matrix. The vector is treated as a single-row matrix, multiplied
     * by the given Matrix, and the resulting single row is converted back to a Vector.
     */
    public function testMulMatrix(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = Matrix::fromArray([
            [1, 4],
            [2, 5],
            [3, 6],
        ]);
        $result = $v->mulMatrix($m);

        $this->assertInstanceOf(Vector::class, $result);
        // [1,2,3] * M = [1*1+2*2+3*3, 1*4+2*5+3*6] = [14, 32]
        $this->assertSame([14.0, 32.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by a Matrix with an incompatible row count throws LengthException.
     */
    public function testMulMatrixIncompatibleDimensionsThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = new Matrix(2, 2);

        $this->expectException(LengthException::class);
        $v->mulMatrix($m);
    }

    #endregion

    #region Method dot() tests.

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

    #endregion

    #region Method normalized() tests.

    /**
     * Test normalized produces a unit vector.
     */
    public function testNormalizedProducesUnitVector(): void
    {
        $v = Vector::fromArray([3, 4]);
        $unit = $v->normalized();

        $this->assertNotNull($unit->magnitude);
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

        $this->assertNotNull($unit->magnitude);
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
