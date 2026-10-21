<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Vector;

use ArrayIterator;
use Mossy2100\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorIteratorAggregateTest extends TestCase
{
    #region Method getIterator() tests.

    /**
     * Test foreach yields the elements with their indexes.
     */
    public function testForeachYieldsElements(): void
    {
        $v = Vector::fromArray([1, 2.5, -3]);

        $result = [];
        foreach ($v as $index => $element) {
            $result[$index] = $element;
        }

        $this->assertSame([1.0, 2.5, -3.0], $result);
    }

    /**
     * Test getIterator() returns an ArrayIterator matching toArray().
     */
    public function testGetIteratorMatchesToArray(): void
    {
        $v = Vector::fromArray([4, 5, 6]);

        $this->assertInstanceOf(ArrayIterator::class, $v->getIterator());
        $this->assertSame($v->toArray(), iterator_to_array($v));
    }

    /**
     * Test iterating an empty Vector yields nothing.
     */
    public function testIterateEmpty(): void
    {
        $this->assertSame([], iterator_to_array(new Vector(0)));
    }

    /**
     * Test the elements are a snapshot, so changing the Vector during the loop doesn't change the values still to come.
     */
    public function testIterationIsSnapshot(): void
    {
        $v = Vector::fromArray([1, 2, 3]);

        $result = [];
        foreach ($v as $index => $element) {
            if ($index === 0) {
                $v[2] = 99;
            }
            $result[] = $element;
        }

        $this->assertSame([1.0, 2.0, 3.0], $result);
        $this->assertSame(99.0, $v[2]);
    }

    #endregion
}
