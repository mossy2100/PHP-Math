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
class VectorArithmeticTest extends TestCase
{
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
     * Test multiplying a vector by a Matrix. The vector is treated as a single-row matrix, multiplied
     * by the given Matrix, and the resulting single row is converted back to a Vector.
     */
    public function testMulByMatrix(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = Matrix::fromArray([
            [1, 4],
            [2, 5],
            [3, 6],
        ]);
        $result = $v->mul($m);

        $this->assertInstanceOf(Vector::class, $result);
        // [1,2,3] * M = [1*1+2*2+3*3, 1*4+2*5+3*6] = [14, 32]
        $this->assertSame([14.0, 32.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by a Matrix with an incompatible row count throws LengthException.
     */
    public function testMulByMatrixIncompatibleDimensionsThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = new Matrix(2, 2);

        $this->expectException(LengthException::class);
        $v->mul($m);
    }

    /**
     * Test dividing a vector by a scalar.
     */
    public function testDiv(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $result = $v->div(10);
        $this->assertEqualsWithDelta([1.0, 2.0, 3.0], $result->toArray(), EPSILON);
    }

    /**
     * Test dividing a vector by zero throws ArithmeticException.
     */
    public function testDivByZeroThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->expectException(ArithmeticException::class);
        $v->div(0);
    }

    /**
     * Test the Hadamard (element-wise) product of two vectors.
     */
    public function testHadamard(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([4, 5, 6]);
        $result = $a->hadamard($b);
        $this->assertEqualsWithDelta([4.0, 10.0, 18.0], $result->toArray(), EPSILON);
    }

    /**
     * Test the Hadamard product of vectors with different sizes throws LengthException.
     */
    public function testHadamardWithDifferentSizesThrows(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2]);
        $this->expectException(LengthException::class);
        $a->hadamard($b);
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

    #endregion

    #region normalize() tests

    /**
     * Test normalize produces a unit vector.
     */
    public function testNormalizeProducesUnitVector(): void
    {
        $v = Vector::fromArray([3, 4]);
        $unit = $v->normalize();

        $this->assertNotNull($unit->magnitude);
        $this->assertEqualsWithDelta(1.0, $unit->magnitude, EPSILON);
        $this->assertEqualsWithDelta(3.0 / 5.0, $unit->get(0), EPSILON);
        $this->assertEqualsWithDelta(4.0 / 5.0, $unit->get(1), EPSILON);
    }

    /**
     * Test normalize on a unit vector returns equivalent vector.
     */
    public function testNormalizeUnitVector(): void
    {
        $v = Vector::fromArray([1, 0, 0]);
        $unit = $v->normalize();

        $this->assertNotNull($unit->magnitude);
        $this->assertEqualsWithDelta(1.0, $unit->magnitude, EPSILON);
        $this->assertSame(1.0, $unit->get(0));
        $this->assertSame(0.0, $unit->get(1));
        $this->assertSame(0.0, $unit->get(2));
    }

    /**
     * Test normalize on zero vector throws ArithmeticException.
     */
    public function testNormalizeZeroVectorThrows(): void
    {
        $v = new Vector(3);
        $this->expectException(ArithmeticException::class);
        $v->normalize();
    }

    #endregion

    #region sum()/prod() tests

    /**
     * Test sum of a vector's elements.
     */
    public function testSum(): void
    {
        $v = Vector::fromArray([1, 2, 3, 4]);
        $this->assertEqualsWithDelta(10.0, $v->sum(), EPSILON);
    }

    /**
     * Test sum of an empty vector is 0 (the additive identity).
     */
    public function testSumWithEmptyVector(): void
    {
        $v = new Vector(0);
        $this->assertSame(0.0, $v->sum());
    }

    /**
     * Test product of a vector's elements.
     */
    public function testProd(): void
    {
        $v = Vector::fromArray([1, 2, 3, 4]);
        $this->assertEqualsWithDelta(24.0, $v->prod(), EPSILON);
    }

    /**
     * Test product of a vector containing a zero element is 0.
     */
    public function testProdWithZeroElement(): void
    {
        $v = Vector::fromArray([1, 2, 0, 4]);
        $this->assertSame(0.0, $v->prod());
    }

    /**
     * Test product of an empty vector is 1 (the multiplicative identity).
     */
    public function testProdWithEmptyVector(): void
    {
        $v = new Vector(0);
        $this->assertSame(1.0, $v->prod());
    }

    #endregion
}
