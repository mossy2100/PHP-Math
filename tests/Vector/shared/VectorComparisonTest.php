<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use InvalidArgumentException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorComparisonTest extends TestCase
{
    #region Method equal() tests.

    /**
     * Test equal with identical vectors.
     */
    public function testEqualWithIdenticalVectors(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([1, 2, 3]);
        $this->assertTrue($v1->equal($v2));
    }

    /**
     * Test equal with different values.
     */
    public function testEqualWithDifferentValues(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([1, 2, 4]);
        $this->assertFalse($v1->equal($v2));
    }

    /**
     * Test equal with different sizes.
     */
    public function testEqualWithDifferentSizes(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([1, 2]);
        $this->assertFalse($v1->equal($v2));
    }

    /**
     * Test equal with an array throws InvalidArgumentException (arrays are not a supported type).
     */
    public function testEqualWithArrayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $v = Vector::fromArray([1, 2, 3]);
        $v->equal([1, 2, 3]);
    }

    /**
     * Test equal with a Matrix throws InvalidArgumentException (Matrix is not a supported type), even
     * a single-column Matrix that could plausibly represent the same values.
     */
    public function testEqualWithMatrixThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $col = new Matrix(3, 1);
        $col->setColumn(0, Vector::fromArray([1, 2, 3]));

        $this->expectException(InvalidArgumentException::class);
        $v->equal($col);
    }

    /**
     * Test equal with a string throws InvalidArgumentException.
     */
    public function testEqualWithStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $v = Vector::fromArray([1, 2, 3]);
        $v->equal('not a vector');
    }

    /**
     * Test equal with an int throws InvalidArgumentException.
     */
    public function testEqualWithIntThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $v = Vector::fromArray([1, 2, 3]);
        $v->equal(42);
    }

    /**
     * Test equal with null throws InvalidArgumentException.
     */
    public function testEqualWithNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $v = Vector::fromArray([1, 2, 3]);
        $v->equal(null);
    }

    #endregion

    #region Method approxEqual() tests.

    /**
     * Test approxEqual with close values.
     */
    public function testApproxEqualWithCloseValues(): void
    {
        $v1 = Vector::fromArray([1.0, 2.0, 3.0]);
        $v2 = Vector::fromArray([
            1.0 + 1e-12,
            2.0 - 1e-12,
            3.0 + 1e-12,
        ]);
        $this->assertTrue($v1->approxEqual($v2));
    }

    /**
     * Test approxEqual with values outside tolerance.
     */
    public function testApproxEqualWithValuesOutsideTolerance(): void
    {
        $v1 = Vector::fromArray([1.0, 2.0, 3.0]);
        $v2 = Vector::fromArray([1.1, 2.0, 3.0]);
        $this->assertFalse($v1->approxEqual($v2));
    }

    /**
     * Test approxEqual with different sizes.
     */
    public function testApproxEqualWithDifferentSizes(): void
    {
        $v1 = Vector::fromArray([1.0, 2.0, 3.0]);
        $v2 = Vector::fromArray([1.0, 2.0]);
        $this->assertFalse($v1->approxEqual($v2));
    }

    /**
     * Test approxEqual with an array throws InvalidArgumentException (arrays are not a supported type).
     */
    public function testApproxEqualWithArrayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $v->approxEqual([1.0, 2.0, 3.0]);
    }

    /**
     * Test approxEqual with a Matrix throws InvalidArgumentException (Matrix is not a supported type),
     * even a single-column Matrix that could plausibly represent the same values.
     */
    public function testApproxEqualWithMatrixThrows(): void
    {
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $col = new Matrix(3, 1);
        $col->setColumn(0, Vector::fromArray([1.0, 2.0, 3.0]));

        $this->expectException(InvalidArgumentException::class);
        $v->approxEqual($col);
    }

    /**
     * Test approxEqual with a string throws InvalidArgumentException.
     */
    public function testApproxEqualWithStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $v->approxEqual('not a vector');
    }

    /**
     * Test approxEqual with an int throws InvalidArgumentException.
     */
    public function testApproxEqualWithIntThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $v->approxEqual(42);
    }

    /**
     * Test approxEqual with null throws InvalidArgumentException.
     */
    public function testApproxEqualWithNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $v->approxEqual(null);
    }

    #endregion
}
