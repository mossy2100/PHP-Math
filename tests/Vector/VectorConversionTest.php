<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorConversionTest extends TestCase
{
    #region Method toArray() tests.

    /**
     * Test toArray returns a copy of the data.
     */
    public function testToArrayReturnsCopy(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $arr = $v->toArray();
        $this->assertSame([1.0, 2.0, 3.0], $arr);

        // Modifying the returned array should not affect the vector.
        $arr[0] = 99;
        $this->assertSame([1.0, 2.0, 3.0], $v->toArray());
    }

    #endregion

    #region Method toColumnMatrix() tests.

    /**
     * Test toColumnMatrix returns a single-column matrix.
     */
    public function testToColumnMatrix(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = $v->toColumnMatrix();
        $this->assertInstanceOf(Matrix::class, $m);
        $this->assertSame(3, $m->rowCount);
        $this->assertSame(1, $m->columnCount);
    }

    #endregion

    #region Method toRowMatrix() tests.

    /**
     * Test toRowMatrix returns a single-row matrix.
     */
    public function testToRowMatrix(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = $v->toRowMatrix();
        $this->assertInstanceOf(Matrix::class, $m);
        $this->assertSame(1, $m->rowCount);
        $this->assertSame(3, $m->columnCount);
    }

    #endregion

    #region toColumnMatrix()/toRowMatrix() empty vector tests.

    /**
     * Test toColumnMatrix/toRowMatrix with an empty vector still produce a properly-shaped n×1 or
     * 1×n matrix (0×1 and 1×0 respectively), not a degenerate 0×0 matrix.
     */
    public function testToMatrixWithEmptyVector(): void
    {
        $v = new Vector(0);

        $col = $v->toColumnMatrix();
        $this->assertInstanceOf(Matrix::class, $col);
        $this->assertSame(0, $col->rowCount);
        $this->assertSame(1, $col->columnCount);

        $row = $v->toRowMatrix();
        $this->assertInstanceOf(Matrix::class, $row);
        $this->assertSame(1, $row->rowCount);
        $this->assertSame(0, $row->columnCount);
    }

    #endregion

    #region Method __toString() tests.

    /**
     * Test __toString uses ordered tuple notation with mathematical angle brackets.
     */
    public function testToString(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->assertSame('⟨1, 2, 3⟩', (string) $v);
    }

    #endregion
}
