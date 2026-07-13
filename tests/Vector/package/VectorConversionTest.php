<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use InvalidArgumentException;
use LogicException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorConversionTest extends TestCase
{
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

    /**
     * Test __toString uses ordered tuple notation with mathematical angle brackets.
     */
    public function testToString(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->assertSame('⟨1, 2, 3⟩', (string) $v);
    }

    /**
     * Test offsetExists with valid indices.
     */
    public function testOffsetExistsWithValidIndex(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->assertTrue($v->offsetExists(0));
        $this->assertTrue($v->offsetExists(1));
        $this->assertTrue($v->offsetExists(2));
    }

    /**
     * Test offsetExists with invalid indices.
     */
    public function testOffsetExistsWithInvalidIndex(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->assertFalse($v->offsetExists(3));
        $this->assertFalse($v->offsetExists(-1));
    }

    /**
     * Test offsetGet with a valid index.
     */
    public function testOffsetGetWithValidIndex(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->assertSame(10.0, $v[0]);
        $this->assertSame(20.0, $v[1]);
        $this->assertSame(30.0, $v[2]);
    }

    /**
     * Test offsetGet with an invalid index throws OutOfRangeException.
     */
    public function testOffsetGetWithInvalidIndexThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $x = $v[5];
    }

    /**
     * Test offsetSet with a valid index and value.
     */
    public function testOffsetSetWithValidIndexAndValue(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $v[1] = 99;
        $this->assertSame(99.0, $v[1]);
    }

    /**
     * Test offsetSet with an invalid index throws OutOfRangeException.
     */
    public function testOffsetSetWithInvalidIndexThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->expectException(OutOfRangeException::class);
        $v[5] = 10;
    }

    /**
     * Test offsetSet with a non-number value throws InvalidArgumentException.
     */
    public function testOffsetSetWithNonNumberThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->expectException(InvalidArgumentException::class);
        $v[0] = 'hello';
    }

    /**
     * Test offsetUnset throws LogicException.
     */
    public function testOffsetUnsetThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->expectException(LogicException::class);
        unset($v[0]);
    }

    /**
     * Test array bracket syntax for reading, writing, and checking existence.
     */
    public function testArrayBracketSyntax(): void
    {
        $v = Vector::fromArray([10, 20, 30]);

        // Read via brackets.
        $this->assertSame(10.0, $v[0]);
        $this->assertSame(30.0, $v[2]);

        // Write via brackets.
        $v[1] = 5;
        $this->assertSame(5.0, $v[1]);

        // Existence check via offsetExists.
        $this->assertTrue($v->offsetExists(0));
        $this->assertFalse($v->offsetExists(3));
    }
}
