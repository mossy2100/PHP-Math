<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use DomainException;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorFactoryTest extends TestCase
{
    #region Method fromArray() tests.

    /**
     * Test fromArray with integer values.
     */
    public function testFromArrayWithInts(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->assertSame([1.0, 2.0, 3.0], $v->toArray());
        $this->assertSame(3, $v->count);
    }

    /**
     * Test fromArray with float values.
     */
    public function testFromArrayWithFloats(): void
    {
        $v = Vector::fromArray([1.5, 2.5, 3.5]);
        $this->assertSame([1.5, 2.5, 3.5], $v->toArray());
    }

    /**
     * Test fromArray with mixed int and float values.
     */
    public function testFromArrayWithMixed(): void
    {
        $v = Vector::fromArray([1, 2.5, 3]);
        $this->assertSame([1.0, 2.5, 3.0], $v->toArray());
    }

    /**
     * Test fromArray with empty array creates a count-zero vector.
     */
    public function testFromArrayWithEmptyArray(): void
    {
        $v = Vector::fromArray([]);
        $this->assertSame(0, $v->count);
        $this->assertSame([], $v->toArray());
    }

    /**
     * Test fromArray with non-numeric values throws DomainException.
     */
    public function testFromArrayWithNonNumericThrows(): void
    {
        $this->expectException(DomainException::class);
        Vector::fromArray([1, 'hello', 3]);
    }

    /**
     * Test fromArray with a non-sequential (non-list) array throws DomainException.
     */
    public function testFromArrayWithNonSequentialArrayThrows(): void
    {
        $this->expectException(DomainException::class);
        Vector::fromArray([
            5  => 10,
            10 => 20,
            15 => 30,
        ]);
    }

    /**
     * Data provider for non-finite element values.
     *
     * @return array<string, list<float>>
     */
    public static function nonFiniteElementProvider(): array
    {
        return [
            'positive infinity' => [INF],
            'negative infinity' => [-INF],
            'NAN'               => [NAN],
        ];
    }

    /**
     * Test fromArray with a non-finite element throws DomainException.
     *
     * @param float $value The non-finite element value.
     */
    #[DataProvider('nonFiniteElementProvider')]
    public function testFromArrayWithNonFiniteElementThrows(float $value): void
    {
        $this->expectException(DomainException::class);
        Vector::fromArray([1.0, $value]);
    }

    #endregion
}
