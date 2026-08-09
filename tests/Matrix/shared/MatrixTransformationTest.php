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
