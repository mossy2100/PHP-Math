<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use LengthException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixModificationTest extends TestCase
{
    #region Method set() tests.

    /**
     * Test setting a valid element.
     */
    public function testSetValidElement(): void
    {
        $m = new Matrix(2, 2);
        $m->set(0, 1, 42);
        $this->assertSame(42.0, $m->get(0, 1));
    }

    /**
     * Test setting a float element.
     */
    public function testSetFloatElement(): void
    {
        $m = new Matrix(2, 2);
        $m->set(1, 0, 3.14);
        $this->assertSame(3.14, $m->get(1, 0));
    }

    /**
     * Test setting an element out of range throws OutOfRangeException.
     */
    public function testSetOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $m->set(2, 0, 1);
    }

    /**
     * Test setting an element with a negative index throws OutOfRangeException.
     */
    public function testSetNegativeIndexThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $m->set(0, -1, 1);
    }

    /**
     * Test setting a non-finite value throws DomainException.
     */
    public function testSetNonFiniteValueThrows(): void
    {
        $m = new Matrix(2, 2);

        $this->expectException(DomainException::class);
        $m->set(0, 0, INF);
    }

    /**
     * Test setting NAN throws DomainException.
     */
    public function testSetNanValueThrows(): void
    {
        $m = new Matrix(2, 2);

        $this->expectException(DomainException::class);
        $m->set(0, 0, NAN);
    }

    /**
     * Test setting negative infinity throws DomainException.
     */
    public function testSetNegativeInfinityValueThrows(): void
    {
        $m = new Matrix(2, 2);

        $this->expectException(DomainException::class);
        $m->set(0, 0, -INF);
    }

    #endregion

    #region Method setRow() tests.

    /**
     * Test setRow with a Vector.
     */
    public function testSetRowWithVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $m->setRow(1, Vector::fromArray([10, 11, 12]));

        $this->assertSame([1.0, 2.0, 3.0], $m->getRow(0)->toArray());
        $this->assertSame([10.0, 11.0, 12.0], $m->getRow(1)->toArray());
    }

    /**
     * Test setRow with out of range index throws OutOfRangeException.
     */
    public function testSetRowOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $m->setRow(2, Vector::fromArray([1, 2, 3]));
    }

    /**
     * Test setRow with wrong length throws LengthException.
     */
    public function testSetRowWrongLengthThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(LengthException::class);
        $m->setRow(0, Vector::fromArray([1, 2]));
    }

    /**
     * Test setRow does not alias the caller's Vector: mutating it after the call does not affect the Matrix.
     */
    public function testSetRowDoesNotAliasCallerVector(): void
    {
        $m = new Matrix(2, 3);
        $vec = Vector::fromArray([1, 2, 3]);
        $m->setRow(0, $vec);

        $vec->set(0, 999);

        $this->assertSame([1.0, 2.0, 3.0], $m->getRow(0)->toArray());
    }

    #endregion

    #region Method setColumn() tests.

    /**
     * Test setColumn with a Vector.
     */
    public function testSetColumnWithVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $m->setColumn(2, Vector::fromArray([30, 60]));

        $this->assertSame([1.0, 2.0, 30.0], $m->getRow(0)->toArray());
        $this->assertSame([4.0, 5.0, 60.0], $m->getRow(1)->toArray());
    }

    /**
     * Test setColumn with out of range index throws OutOfRangeException.
     */
    public function testSetColumnOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $m->setColumn(3, Vector::fromArray([1, 2]));
    }

    /**
     * Test setColumn with wrong length throws LengthException.
     */
    public function testSetColumnWrongLengthThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(LengthException::class);
        $m->setColumn(0, Vector::fromArray([1, 2, 3]));
    }

    #endregion

    #region Method paste() tests.

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
}
