<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use LengthException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorLinearAlgebraTest extends TestCase
{
    use FloatAssertions;

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
        $v1 = Vector::fromArray([1, 0, 0]);
        $v2 = Vector::fromArray([0, 1, 0]);
        $result = $v1->cross($v2);
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

    /**
     * Test cross product of huge parallel vectors is zero, even though the intermediate products overflow.
     */
    public function testCrossHugeParallelVectors(): void
    {
        $v = Vector::fromArray([1e300, 1e300, 1e300]);
        $this->assertSame([0.0, 0.0, 0.0], $v->cross($v)->toArray());
        $this->assertSame([0.0, 0.0, 0.0], $v->cross($v->mul(-1.0))->toArray());
    }

    /**
     * Test cross product of huge vectors whose result fits, though the intermediate products overflow.
     */
    public function testCrossHugeVectorsResultFits(): void
    {
        // (1e300, 1, 0) × (1e300, 2, 0) = (0, 0, 2e300 - 1e300).
        $v1 = Vector::fromArray([1e300, 1.0, 0.0]);
        $v2 = Vector::fromArray([1e300, 2.0, 0.0]);
        $result = $v1->cross($v2)->toArray();
        $this->assertSame(0.0, $result[0]);
        $this->assertSame(0.0, $result[1]);
        $this->assertApproxEqual(1e300, $result[2]);

        // A huge vector crossed with a tiny one: (1e300, 0, 0) × (0, 1e-300, 0) = (0, 0, 1).
        $result = Vector::fromArray([1e300, 0.0, 0.0])->cross(Vector::fromArray([0.0, 1e-300, 0.0]))->toArray();
        $this->assertApproxEqual(1.0, $result[2]);
    }

    /**
     * Test cross product throws when the result is too large to represent.
     */
    public function testCrossOverflowThrows(): void
    {
        $v1 = Vector::fromArray([1e200, 1e200, 0.0]);
        $v2 = Vector::fromArray([1e200, -1e200, 0.0]);
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessageMatches('/^Cannot compute cross product of .+\. The result would overflow\.$/');
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

    #region Method normalize() tests.

    /**
     * Test normalize() produces a unit vector.
     */
    public function testNormalizeProducesUnitVector(): void
    {
        $v = Vector::fromArray([3, 4]);
        $unit = $v->normalize();

        $this->assertEqualsWithDelta(1.0, $unit->magnitude, EPSILON);
        $this->assertEqualsWithDelta(3.0 / 5.0, $unit->get(0), EPSILON);
        $this->assertEqualsWithDelta(4.0 / 5.0, $unit->get(1), EPSILON);
    }

    /**
     * Test normalize() returns a new vector, leaving the original unchanged.
     */
    public function testNormalizeLeavesOriginalUnchanged(): void
    {
        $v = Vector::fromArray([3, 4]);
        $unit = $v->normalize();

        $this->assertNotSame($v, $unit);
        $this->assertSame([3.0, 4.0], $v->toArray());
    }

    /**
     * Test normalize() on a unit vector returns an equivalent vector.
     */
    public function testNormalizeUnitVector(): void
    {
        $v = Vector::fromArray([1, 0, 0]);
        $unit = $v->normalize();

        $this->assertEqualsWithDelta(1.0, $unit->magnitude, EPSILON);
        $this->assertSame(1.0, $unit->get(0));
        $this->assertSame(0.0, $unit->get(1));
        $this->assertSame(0.0, $unit->get(2));
    }

    /**
     * Test normalize() on a zero vector throws ArithmeticException.
     */
    public function testNormalizeZeroVectorThrows(): void
    {
        $v = new Vector(3);
        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot normalize zero Vector.');
        $v->normalize();
    }

    /**
     * Test normalize() on an empty vector throws ArithmeticException.
     */
    public function testNormalizeEmptyVectorThrows(): void
    {
        $v = new Vector(0);
        $this->expectException(ArithmeticException::class);
        $v->normalize();
    }

    /**
     * Data for vectors whose elements would overflow or underflow if squared.
     *
     * @return array<string, array{list<float>, list<float>}> Vector elements, expected unit vector elements.
     */
    public static function normalizeExtremeProvider(): array
    {
        return [
            'huge'                => [
                [3e300, 4e300],
                [0.6, 0.8],
            ],
            'huge, magnitude INF' => [
                [1.5e308, -1.5e308],
                [M_SQRT1_2, -M_SQRT1_2],
            ],
            'tiny'                => [
                [3e-200, 4e-200],
                [0.6, 0.8],
            ],
            'subnormal'           => [
                [0.0, -5e-324],
                [0.0, -1.0],
            ],
        ];
    }

    /**
     * Test normalize() gives a unit vector for a vector whose elements would overflow or underflow if squared.
     *
     * @param list<float> $elements The vector elements.
     * @param list<float> $expected The expected unit vector elements.
     */
    #[DataProvider('normalizeExtremeProvider')]
    public function testNormalizeExtreme(array $elements, array $expected): void
    {
        $unit = Vector::fromArray($elements)->normalize();

        foreach ($expected as $i => $value) {
            $this->assertApproxEqual($value, $unit[$i]);
        }
    }

    #endregion
}
