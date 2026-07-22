<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use InvalidArgumentException;
use LogicException;
use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorArrayAccessTest extends TestCase
{
    #region Method offsetExists() tests.

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

    #endregion

    #region Method offsetGet() tests.

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
     * Test offsetGet with a non-integer offset throws InvalidArgumentException.
     */
    public function testOffsetGetWithNonIntegerOffsetThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(InvalidArgumentException::class);
        $x = $v['first'];
    }

    #endregion

    #region Method offsetSet() tests.

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
     * Test offsetSet with a non-integer offset throws InvalidArgumentException.
     */
    public function testOffsetSetWithNonIntegerOffsetThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->expectException(InvalidArgumentException::class);
        $v['first'] = 10;
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

    #endregion

    #region Method offsetUnset() tests.

    /**
     * Test offsetUnset throws LogicException.
     */
    public function testOffsetUnsetThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->expectException(LogicException::class);
        unset($v[0]);
    }

    #endregion

    #region Array bracket syntax tests.

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

    #endregion
}
