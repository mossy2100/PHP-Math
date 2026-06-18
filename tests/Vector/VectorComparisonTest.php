<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorComparisonTest extends TestCase
{
    /**
     * Test equal with identical vectors.
     */
    public function testEqualWithIdenticalVectors(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2, 3]);
        $this->assertTrue($a->equal($b));
    }

    /**
     * Test equal with different values.
     */
    public function testEqualWithDifferentValues(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2, 4]);
        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal with different sizes.
     */
    public function testEqualWithDifferentSizes(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2]);
        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal with non-Vector returns false.
     */
    public function testEqualWithNonVectorReturnsFalse(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->assertFalse($v->equal('not a vector'));
        $this->assertFalse($v->equal(42));
        $this->assertFalse($v->equal(null));
    }

    /**
     * Test approxEqual with close values.
     */
    public function testApproxEqualWithCloseValues(): void
    {
        $a = Vector::fromArray([1.0, 2.0, 3.0]);
        $b = Vector::fromArray([1.0 + 1e-12, 2.0 - 1e-12, 3.0 + 1e-12]);
        $this->assertTrue($a->approxEqual($b));
    }

    /**
     * Test approxEqual with values outside tolerance.
     */
    public function testApproxEqualWithValuesOutsideTolerance(): void
    {
        $a = Vector::fromArray([1.0, 2.0, 3.0]);
        $b = Vector::fromArray([1.1, 2.0, 3.0]);
        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual with different sizes.
     */
    public function testApproxEqualWithDifferentSizes(): void
    {
        $a = Vector::fromArray([1.0, 2.0, 3.0]);
        $b = Vector::fromArray([1.0, 2.0]);
        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual with non-Vector returns false.
     */
    public function testApproxEqualWithNonVectorReturnsFalse(): void
    {
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $this->assertFalse($v->approxEqual('not a vector'));
        $this->assertFalse($v->approxEqual(42));
        $this->assertFalse($v->approxEqual(null));
    }
}
