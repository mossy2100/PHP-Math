<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Matrix;

use Mossy2100\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixPropertiesTest extends TestCase
{
    #region Property $rowCount tests.

    /**
     * Test $rowCount reflects the number of rows passed to the constructor.
     */
    public function testRowCount(): void
    {
        $m1 = new Matrix(2, 3);
        $this->assertSame(2, $m1->rowCount);

        $m2 = new Matrix(5, 5);
        $this->assertSame(5, $m2->rowCount);

        // PHPStan catches write attempts at static analysis time; at runtime, private(set) prevents
        // modification.
    }

    /**
     * Test $rowCount is zero for a matrix with no rows.
     */
    public function testRowCountZero(): void
    {
        $m = new Matrix(0, 3);
        $this->assertSame(0, $m->rowCount);
    }

    /**
     * Test $rowCount reflects the number of rows in an array-constructed matrix.
     */
    public function testRowCountFromArray(): void
    {
        $m = Matrix::fromArray([[1, 2], [3, 4], [5, 6]]);
        $this->assertSame(3, $m->rowCount);
    }

    #endregion

    #region Property $columnCount tests.

    /**
     * Test $columnCount reflects the number of columns passed to the constructor.
     */
    public function testColumnCount(): void
    {
        $m1 = new Matrix(2, 3);
        $this->assertSame(3, $m1->columnCount);

        $m2 = new Matrix(5, 5);
        $this->assertSame(5, $m2->columnCount);

        // PHPStan catches write attempts at static analysis time; at runtime, private(set) prevents
        // modification.
    }

    /**
     * Test $columnCount is zero for a matrix with no columns.
     */
    public function testColumnCountZero(): void
    {
        $m = new Matrix(3, 0);
        $this->assertSame(0, $m->columnCount);
    }

    /**
     * Test $columnCount reflects the number of columns in an array-constructed matrix.
     */
    public function testColumnCountFromArray(): void
    {
        $m = Matrix::fromArray([[1, 2], [3, 4], [5, 6]]);
        $this->assertSame(2, $m->columnCount);
    }

    #endregion
}
