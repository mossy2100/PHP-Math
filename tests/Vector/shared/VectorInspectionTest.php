<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Vector;

use Mossy2100\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorInspectionTest extends TestCase
{
    #region Method get() tests.

    /**
     * Test get() returns the correct element value.
     */
    public function testGetReturnsCorrectValue(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->assertSame(10.0, $v->get(0));
        $this->assertSame(20.0, $v->get(1));
        $this->assertSame(30.0, $v->get(2));
    }

    /**
     * Test get() with a negative index throws OutOfRangeException.
     */
    public function testGetWithNegativeIndexThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $v->get(-1);
    }

    /**
     * Test get() with an index beyond the last element throws OutOfRangeException.
     */
    public function testGetWithIndexBeyondSizeThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $v->get(3);
    }

    /**
     * Test get() on an empty vector throws OutOfRangeException.
     */
    public function testGetOnEmptyVectorThrows(): void
    {
        $v = new Vector(0);
        $this->expectException(OutOfRangeException::class);
        $v->get(0);
    }

    /**
     * Test get() names the valid range when the index is outside it, or says the Vector is empty.
     */
    public function testGetOutOfRangeMessages(): void
    {
        try {
            Vector::fromArray([1, 2, 3])->get(3);
            $this->fail('Expected OutOfRangeException.');
        } catch (OutOfRangeException $e) {
            $this->assertSame('Invalid index: 3. Must be in the range 0-2.', $e->getMessage());
        }

        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Invalid index: 0. The Vector is empty.');
        new Vector(0)->get(0);
    }

    #endregion
}
