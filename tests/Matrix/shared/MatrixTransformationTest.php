<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixTransformationTest extends TestCase
{
    #region Method resize() tests.

    /**
     * Test resize to a larger matrix zero-fills the new rows and columns.
     */
    public function testResizeGrow(): void
    {
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $r = $mat->resize(3, 3);
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
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);

        $r = $mat->resize(2, 2);
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
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);

        $r = $mat->resize(4, 1);
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
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $r = $mat->resize(2, 2);
        $this->assertTrue($mat->equal($r));
        $this->assertNotSame($mat, $r);
    }

    /**
     * Test resize to 0x0.
     */
    public function testResizeToZero(): void
    {
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $r = $mat->resize(0, 0);
        $this->assertSame(0, $r->rowCount);
        $this->assertSame(0, $r->columnCount);
    }

    /**
     * Test resize from a 0x0 matrix grows with zero-fill.
     */
    public function testResizeFromZero(): void
    {
        $mat = new Matrix(0, 0);

        $r = $mat->resize(2, 2);
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
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $r = $mat->resize(1, 1);
        $r->set(0, 0, 99);

        $this->assertSame([
            [1.0, 2.0],
            [3.0, 4.0],
        ], $mat->toArray());
    }

    /**
     * Test resize with a negative dimension throws DomainException, delegated from the constructor.
     */
    public function testResizeWithNegativeDimensionThrows(): void
    {
        $mat = Matrix::identity(2);
        $this->expectException(DomainException::class);
        $mat->resize(-1, 2);
    }

    #endregion
}
