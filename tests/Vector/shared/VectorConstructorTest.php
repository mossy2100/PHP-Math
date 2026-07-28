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
     * Test constructor with count zero creates an empty vector.
     */
    public function testConstructorWithCountZero(): void
    {
        $v = new Vector(0);
        $this->assertSame(0, $v->count);
    }

    /**
     * Test constructor with count one creates a single-element vector.
     */
    public function testConstructorWithCountOne(): void
    {
        $v = new Vector(1);
        $this->assertSame(1, $v->count);
    }

    /**
     * Test constructor with count five creates a five-element vector.
     */
    public function testConstructorWithCountFive(): void
    {
        $v = new Vector(5);
        $this->assertSame(5, $v->count);
    }

    /**
     * Test constructor with negative count throws DomainException.
     */
    public function testConstructorWithNegativeCountThrows(): void
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
