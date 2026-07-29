<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use InvalidArgumentException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixComparisonTest extends TestCase
{
    #region Method equal() tests.

    /**
     * Test equal with identical matrices.
     */
    public function testEqualWithIdenticalMatrices(): void
    {
        $matA = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $matB = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $this->assertTrue($matA->equal($matB));
    }

    /**
     * Test equal with different values but same dimensions.
     */
    public function testEqualWithDifferentValues(): void
    {
        $matA = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $matB = Matrix::fromArray([
            [1, 2],
            [3, 5],
        ]);
        $this->assertFalse($matA->equal($matB));
    }

    /**
     * Test equal with different dimensions.
     */
    public function testEqualWithDifferentDimensions(): void
    {
        $matA = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $matB = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertFalse($matA->equal($matB));
    }

    /**
     * Test equal with an array throws InvalidArgumentException (arrays are not a supported type).
     */
    public function testEqualWithArrayThrows(): void
    {
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $mat->equal([
            [1, 2],
            [3, 4],
        ]);
    }

    /**
     * Test equal with a Vector throws InvalidArgumentException (Vector is not a supported type), even
     * a Vector that could plausibly represent the same values as a single-column Matrix.
     */
    public function testEqualWithVectorThrows(): void
    {
        $mat = Matrix::fromArray([
            [1],
            [2],
            [3],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $mat->equal(Vector::fromArray([1, 2, 3]));
    }

    /**
     * Test equal with a string throws InvalidArgumentException.
     */
    public function testEqualWithStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $mat->equal('not a matrix');
    }

    /**
     * Test equal with null throws InvalidArgumentException.
     */
    public function testEqualWithNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $mat->equal(null);
    }

    /**
     * Test equal with an int throws InvalidArgumentException.
     */
    public function testEqualWithIntThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $mat->equal(42);
    }

    /**
     * Test equal with empty matrices.
     */
    public function testEqualWithEmptyMatrices(): void
    {
        $matA = new Matrix(0, 0);
        $matB = new Matrix(0, 0);
        $this->assertTrue($matA->equal($matB));
    }

    #endregion

    #region Method approxEqual() tests.

    /**
     * Test approxEqual with close values within default tolerance.
     */
    public function testApproxEqualWithCloseValues(): void
    {
        $matA = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $matB = Matrix::fromArray([
            [
                1.0 + 1e-12,
                2.0 - 1e-12,
            ],
            [
                3.0 + 1e-12,
                4.0 - 1e-12,
            ],
        ]);
        $this->assertTrue($matA->approxEqual($matB));
    }

    /**
     * Test approxEqual with values outside tolerance returns false.
     */
    public function testApproxEqualWithValuesOutsideTolerance(): void
    {
        $matA = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $matB = Matrix::fromArray([
            [1.1, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertFalse($matA->approxEqual($matB));
    }

    /**
     * Test approxEqual with different dimensions returns false.
     */
    public function testApproxEqualWithDifferentDimensions(): void
    {
        $matA = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $matB = Matrix::fromArray([
            [1.0, 2.0, 3.0],
        ]);
        $this->assertFalse($matA->approxEqual($matB));
    }

    /**
     * Test approxEqual with an array throws InvalidArgumentException (arrays are not a supported type).
     */
    public function testApproxEqualWithArrayThrows(): void
    {
        $mat = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $mat->approxEqual([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
    }

    /**
     * Test approxEqual with a Vector throws InvalidArgumentException (Vector is not a supported type),
     * even a Vector that could plausibly represent the same values as a single-column Matrix.
     */
    public function testApproxEqualWithVectorThrows(): void
    {
        $mat = Matrix::fromArray([
            [1.0],
            [2.0],
            [3.0],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $mat->approxEqual(Vector::fromArray([1.0, 2.0, 3.0]));
    }

    /**
     * Test approxEqual with a string throws InvalidArgumentException.
     */
    public function testApproxEqualWithStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $mat = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $mat->approxEqual('not a matrix');
    }

    /**
     * Test approxEqual with an int throws InvalidArgumentException.
     */
    public function testApproxEqualWithIntThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $mat = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $mat->approxEqual(42);
    }

    /**
     * Test approxEqual with null throws InvalidArgumentException.
     */
    public function testApproxEqualWithNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $mat = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $mat->approxEqual(null);
    }

    /**
     * Test approxEqual with custom tight tolerance.
     */
    public function testApproxEqualWithCustomTightTolerance(): void
    {
        $matA = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);

        // With an extremely tight tolerance, even tiny differences should fail.
        $close = Matrix::fromArray([
            [
                1.0 + 1e-8,
                2.0,
            ],
            [3.0, 4.0],
        ]);
        $this->assertFalse($matA->approxEqual($close, relTol: 1e-15, absTol: 1e-15));

        // But values that are actually equal should still pass.
        $same = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertTrue($matA->approxEqual($same, relTol: 1e-15, absTol: 1e-15));
    }

    /**
     * Test approxEqual with empty matrices.
     */
    public function testApproxEqualWithEmptyMatrices(): void
    {
        $matA = new Matrix(0, 0);
        $matB = new Matrix(0, 0);
        $this->assertTrue($matA->approxEqual($matB));
    }

    #endregion
}
