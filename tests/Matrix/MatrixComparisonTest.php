<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixComparisonTest extends TestCase
{
    /**
     * Test equal with identical matrices.
     */
    public function testEqualWithIdenticalMatrices(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $this->assertTrue($a->equal($b));
    }

    /**
     * Test equal with different values but same dimensions.
     */
    public function testEqualWithDifferentValues(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [1, 2],
            [3, 5],
        ]);
        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal with different dimensions.
     */
    public function testEqualWithDifferentDimensions(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal with non-Matrix values returns false.
     */
    public function testEqualWithNonMatrixReturnsFalse(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $this->assertFalse($m->equal('not a matrix'));
        $this->assertFalse($m->equal(null));
        $this->assertFalse($m->equal(42));
    }

    /**
     * Test equal with empty matrices.
     */
    public function testEqualWithEmptyMatrices(): void
    {
        $a = new Matrix(0, 0);
        $b = new Matrix(0, 0);
        $this->assertTrue($a->equal($b));
    }

    /**
     * Test approxEqual with close values within default tolerance.
     */
    public function testApproxEqualWithCloseValues(): void
    {
        $a = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $b = Matrix::fromArray([
            [1.0 + 1e-12, 2.0 - 1e-12],
            [3.0 + 1e-12, 4.0 - 1e-12],
        ]);
        $this->assertTrue($a->approxEqual($b));
    }

    /**
     * Test approxEqual with values outside tolerance returns false.
     */
    public function testApproxEqualWithValuesOutsideTolerance(): void
    {
        $a = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $b = Matrix::fromArray([
            [1.1, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual with different dimensions returns false.
     */
    public function testApproxEqualWithDifferentDimensions(): void
    {
        $a = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $b = Matrix::fromArray([
            [1.0, 2.0, 3.0],
        ]);
        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual with non-Matrix values returns false.
     */
    public function testApproxEqualWithNonMatrixReturnsFalse(): void
    {
        $m = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertFalse($m->approxEqual('not a matrix'));
        $this->assertFalse($m->approxEqual(42));
        $this->assertFalse($m->approxEqual(null));
    }

    /**
     * Test approxEqual with custom tight tolerance.
     */
    public function testApproxEqualWithCustomTightTolerance(): void
    {
        $a = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);

        // With an extremely tight tolerance, even tiny differences should fail.
        $close = Matrix::fromArray([
            [1.0 + 1e-8, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertFalse($a->approxEqual($close, relTol: 1e-15, absTol: 1e-15));

        // But values that are actually equal should still pass.
        $same = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertTrue($a->approxEqual($same, relTol: 1e-15, absTol: 1e-15));
    }

    /**
     * Test approxEqual with empty matrices.
     */
    public function testApproxEqualWithEmptyMatrices(): void
    {
        $a = new Matrix(0, 0);
        $b = new Matrix(0, 0);
        $this->assertTrue($a->approxEqual($b));
    }
}
