<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use DomainException;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorConstructorTest extends TestCase
{
    #region Method __construct() tests.

    /**
     * Test constructor with size zero creates an empty vector.
     */
    public function testConstructorWithSizeZero(): void
    {
        $v = new Vector(0);
        $this->assertSame(0, $v->size);
    }

    /**
     * Test constructor with size one creates a single-element vector.
     */
    public function testConstructorWithSizeOne(): void
    {
        $v = new Vector(1);
        $this->assertSame(1, $v->size);
    }

    /**
     * Test constructor with size five creates a five-element vector.
     */
    public function testConstructorWithSizeFive(): void
    {
        $v = new Vector(5);
        $this->assertSame(5, $v->size);
    }

    /**
     * Test constructor with negative size throws DomainException.
     */
    public function testConstructorWithNegativeSizeThrows(): void
    {
        $this->expectException(DomainException::class);
        new Vector(-1);
    }

    /**
     * Test constructor initialises all elements to zero.
     */
    public function testConstructorInitialisesElementsToZero(): void
    {
        $v = new Vector(3);
        $this->assertSame([0.0, 0.0, 0.0], $v->toArray());
    }

    #endregion
}
