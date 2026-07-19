<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use OceanMoon\Math\Matrix;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixTransformationTest extends TestCase
{
    #region copy() tests

    /**
     * Test copy extracts the requested sub-matrix.
     */
    public function testCopy(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);

        $sub = $m->copy(1, 1, 2, 2);
        $this->assertSame(2, $sub->rowCount);
        $this->assertSame(2, $sub->columnCount);
        $this->assertSame([
            [5.0, 6.0],
            [8.0, 9.0],
        ], $sub->toArray());
    }

    /**
     * Test copy from the top-left corner (offset 0, 0).
     */
    public function testCopyFromOrigin(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);

        $sub = $m->copy(0, 0, 1, 2);
        $this->assertSame([
            [1.0, 2.0],
        ], $sub->toArray());
    }

    /**
     * Test copy the entire matrix returns an equal but distinct matrix.
     */
    public function testCopyEntireMatrix(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $sub = $m->copy(0, 0, 2, 2);
        $this->assertTrue($m->equal($sub));
        $this->assertNotSame($m, $sub);
    }

    /**
     * Test copy with zero rowCount or columnCount returns a degenerate matrix.
     */
    public function testCopyWithZeroDimension(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $rows = $m->copy(0, 0, 0, 2);
        $this->assertSame(0, $rows->rowCount);
        $this->assertSame(2, $rows->columnCount);

        $cols = $m->copy(0, 0, 2, 0);
        $this->assertSame(2, $cols->rowCount);
        $this->assertSame(0, $cols->columnCount);
    }

    /**
     * Test copy does not mutate the original matrix.
     */
    public function testCopyDoesNotMutateOriginal(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $sub = $m->copy(0, 0, 1, 1);
        $sub->set(0, 0, 99);

        $this->assertSame(1.0, $m->get(0, 0));
    }

    /**
     * Test copy with a negative row throws OutOfRangeException.
     */
    public function testCopyWithNegativeRowThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->copy(-1, 0, 1, 1);
    }

    /**
     * Test copy with a negative rowCount throws OutOfRangeException.
     */
    public function testCopyWithNegativeRowCountThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->copy(0, 0, -1, 1);
    }

    /**
     * Test copy with a row range extending beyond the matrix throws OutOfRangeException.
     */
    public function testCopyWithRowRangeOutOfBoundsThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->copy(2, 0, 2, 1);
    }

    /**
     * Test copy with a negative column throws OutOfRangeException.
     */
    public function testCopyWithNegativeColumnThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->copy(0, -1, 1, 1);
    }

    /**
     * Test copy with a negative columnCount throws OutOfRangeException.
     */
    public function testCopyWithNegativeColumnCountThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->copy(0, 0, 1, -1);
    }

    /**
     * Test copy with a column range extending beyond the matrix throws OutOfRangeException.
     */
    public function testCopyWithColumnRangeOutOfBoundsThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->copy(0, 2, 1, 2);
    }

    #endregion

    #region paste() tests

    /**
     * Test paste at the default offset (0, 0).
     */
    public function testPasteAtDefaultOffset(): void
    {
        $m = new Matrix(3, 3);
        $m->paste(Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]));

        $this->assertSame([
            [1.0, 2.0, 0.0],
            [3.0, 4.0, 0.0],
            [0.0, 0.0, 0.0],
        ], $m->toArray());
    }

    /**
     * Test paste at a non-zero offset.
     */
    public function testPasteAtOffset(): void
    {
        $m = new Matrix(3, 3);
        $m->paste(Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]), 1, 1);

        $this->assertSame([
            [0.0, 0.0, 0.0],
            [0.0, 1.0, 2.0],
            [0.0, 3.0, 4.0],
        ], $m->toArray());
    }

    /**
     * Test paste overwrites existing elements in the target region.
     */
    public function testPasteOverwritesExistingElements(): void
    {
        $m = Matrix::identity(2);
        $m->paste(Matrix::fromArray([
            [5, 6],
            [7, 8],
        ]));

        $this->assertSame([
            [5.0, 6.0],
            [7.0, 8.0],
        ], $m->toArray());
    }

    /**
     * Test paste mutates the matrix in place.
     */
    public function testPasteMutatesInPlace(): void
    {
        $m = new Matrix(2, 2);
        $m->paste(Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]));

        $this->assertSame([
            [1.0, 2.0],
            [3.0, 4.0],
        ], $m->toArray());
    }

    /**
     * Test paste with a zero-dimension matrix is a no-op.
     */
    public function testPasteWithZeroDimensionMatrix(): void
    {
        $m = Matrix::identity(2);
        $m->paste(new Matrix(0, 0));

        $this->assertSame([
            [1.0, 0.0],
            [0.0, 1.0],
        ], $m->toArray());
    }

    /**
     * Test paste with a negative row offset throws OutOfRangeException.
     */
    public function testPasteWithNegativeRowOffsetThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->paste(new Matrix(1, 1), -1, 0);
    }

    /**
     * Test paste that doesn't fit within the row bounds throws OutOfRangeException.
     */
    public function testPasteRowOutOfBoundsThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->paste(Matrix::fromArray([
            [1],
            [2],
        ]), 2, 0);
    }

    /**
     * Test paste with a negative column offset throws OutOfRangeException.
     */
    public function testPasteWithNegativeColumnOffsetThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->paste(new Matrix(1, 1), 0, -1);
    }

    /**
     * Test paste that doesn't fit within the column bounds throws OutOfRangeException.
     */
    public function testPasteColumnOutOfBoundsThrows(): void
    {
        $m = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $m->paste(Matrix::fromArray([
            [1, 2],
        ]), 0, 2);
    }

    /**
     * Test copy() and paste() composed together, e.g. to embed a 2x2 matrix into the top-left of a
     * 3x3 homogeneous transform, matching the design discussed for this feature.
     */
    public function testCopyAndPasteComposition(): void
    {
        $rotation = Matrix::fromArray([
            [0, -1],
            [1, 0],
        ]);

        $transform = Matrix::identity(3);
        $transform->paste($rotation);

        $this->assertSame([
            [0.0, -1.0, 0.0],
            [1.0, 0.0, 0.0],
            [0.0, 0.0, 1.0],
        ], $transform->toArray());

        // Extracting the same region back out should reproduce the original.
        $extracted = $transform->copy(0, 0, 2, 2);
        $this->assertTrue($rotation->equal($extracted));
    }

    #endregion

    #region resize() tests

    /**
     * Test resize to a larger matrix zero-fills the new rows and columns.
     */
    public function testResizeGrow(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $r = $m->resize(3, 3);
        $this->assertSame([
            [1.0, 2.0, 0.0],
            [3.0, 4.0, 0.0],
            [0.0, 0.0, 0.0],
        ], $r->toArray());
    }

    /**
     * Test resize to a smaller matrix drops excess rows and columns.
     */
    public function testResizeShrink(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);

        $r = $m->resize(2, 2);
        $this->assertSame([
            [1.0, 2.0],
            [4.0, 5.0],
        ], $r->toArray());
    }

    /**
     * Test resize with mixed growth and shrinkage (more rows, fewer columns).
     */
    public function testResizeMixed(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);

        $r = $m->resize(4, 1);
        $this->assertSame([
            [1.0],
            [4.0],
            [0.0],
            [0.0],
        ], $r->toArray());
    }

    /**
     * Test resize to the same dimensions returns an equal but distinct matrix.
     */
    public function testResizeSameDimensions(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $r = $m->resize(2, 2);
        $this->assertTrue($m->equal($r));
        $this->assertNotSame($m, $r);
    }

    /**
     * Test resize to 0x0.
     */
    public function testResizeToZero(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $r = $m->resize(0, 0);
        $this->assertSame(0, $r->rowCount);
        $this->assertSame(0, $r->columnCount);
    }

    /**
     * Test resize from a 0x0 matrix grows with zero-fill.
     */
    public function testResizeFromZero(): void
    {
        $m = new Matrix(0, 0);

        $r = $m->resize(2, 2);
        $this->assertSame([
            [0.0, 0.0],
            [0.0, 0.0],
        ], $r->toArray());
    }

    /**
     * Test resize does not mutate the original matrix.
     */
    public function testResizeDoesNotMutateOriginal(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $r = $m->resize(1, 1);
        $r->set(0, 0, 99);

        $this->assertSame([
            [1.0, 2.0],
            [3.0, 4.0],
        ], $m->toArray());
    }

    /**
     * Test resize with a negative dimension throws DomainException, delegated from the constructor.
     */
    public function testResizeWithNegativeDimensionThrows(): void
    {
        $m = Matrix::identity(2);
        $this->expectException(DomainException::class);
        $m->resize(-1, 2);
    }

    #endregion
}
