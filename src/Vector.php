<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use ArrayAccess;
use DivisionByZeroError;
use DomainException;
use InvalidArgumentException;
use LengthException;
use LogicException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Stringify;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;
use OutOfRangeException;
use Override;
use Stringable;

/**
 * Encapsulates a vector and provides a number of useful methods.
 *
 * @implements ArrayAccess<int, float>
 */
final class Vector implements Stringable, ArrayAccess
{
    use ApproxEquatable;

    #region Private properties

    /**
     * The vector data.
     *
     * @var list<float>
     */
    private array $data;

    #endregion

    #region Public properties

    /**
     * The number of elements in the vector.
     */
    private(set) int $size;

    #endregion

    #region Property hooks

    /**
     * The magnitude (norm) of the vector. Cached on first access and invalidated on mutation.
     */
    private(set) ?float $magnitude = null {
        get {
            // Compute and cache if necessary.
            if ($this->magnitude === null) {
                $this->magnitude = sqrt(array_sum(array_map(static fn ($x) => $x * $x, $this->data)));
            }

            return $this->magnitude;
        }
    }

    #endregion

    #region Constructor

    /**
     * Create a new vector with the specified size.
     *
     * @param int $size Number of elements.
     * @throws DomainException If size is negative.
     */
    public function __construct(int $size)
    {
        if ($size < 0) {
            throw new DomainException("Cannot create a vector with negative size: $size.");
        }

        $this->size = $size;
        $this->data = array_fill(0, $size, 0.0);
    }

    #endregion

    #region Factory methods

    /**
     * Create a vector from an array.
     *
     * @param array<array-key, int|float> $arr Array of numbers.
     * @return self
     * @throws InvalidArgumentException If any array items are not numbers.
     */
    public static function fromArray(array $arr): self
    {
        $data = [];

        // Check if all elements are numbers.
        foreach ($arr as $value) {
            // Check if the value is a number.
            if (!Numbers::isNumber($value)) {
                throw new InvalidArgumentException('Cannot use non-numeric elements in a vector.');
            }

            // Convert the value to a float.
            $data[] = (float) $value;
        }

        // Create the Vector.
        $vector = new self(count($data));
        $vector->data = $data;

        return $vector;
    }

    #endregion

    #region Conversion methods

    /**
     * Get a copy of the vector data as an array.
     *
     * @return list<float> Array of vector elements.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Convert this vector to a Matrix.
     *
     * By default, returns an n×1 column matrix. If $asRow is true, returns a 1×n row matrix.
     * NB: You can also get a row vector by calling $vec->toMatrix()->transpose()
     *
     * @param bool $asRow If true, return a 1×n row matrix; if false (default), return an n×1 column matrix.
     * @return Matrix The matrix representation.
     */
    public function toMatrix(bool $asRow = false): Matrix
    {
        $matrixData = $asRow ? [$this->data] : array_map(static fn ($x) => [$x], $this->data);
        return Matrix::fromArray($matrixData);
    }

    /**
     * Format the vector as a string.
     *
     * @param bool $asRow If true, format as a row vector; if false (default), format as a column vector.
     * @return string String representation of the Vector.
     */
    public function format(bool $asRow = false): string
    {
        return $this->toMatrix($asRow)->__toString();
    }

    /**
     * Convert the vector to a string representation.
     *
     * By default, this will format the Vector as a column vector.
     * If you want to format the column as a row vector, you can use:
     * @example echo $vec->toMatrix(true);
     * OR
     * @example echo $vec->format(true);
     *
     * @return string String representation of the Vector.
     */
    public function __toString(): string
    {
        return $this->format();
    }

    #endregion

    #region Element access

    /**
     * Get a vector element.
     *
     * @param int $index Element index (0-based).
     * @return float Value of the vector element.
     * @throws OutOfRangeException If the index is outside the valid range.
     */
    public function get(int $index): float
    {
        // Check index is valid.
        if ($index < 0 || $index >= count($this->data)) {
            throw new OutOfRangeException(
                "Vector index $index is outside the valid range 0-" . ($this->size - 1) . '.'
            );
        }

        return $this->data[$index];
    }

    /**
     * Set a vector element.
     *
     * @param int $index Element index (0-based).
     * @param float $value Value to set.
     * @throws OutOfRangeException If the index is outside the valid range.
     */
    public function set(int $index, float $value): void
    {
        // Check index is valid.
        if ($index < 0 || $index >= count($this->data)) {
            throw new OutOfRangeException(
                "Vector index $index is outside the valid range 0-" . ($this->size - 1) . '.'
            );
        }

        $this->data[$index] = $value;
        $this->magnitude = null;
    }

    #endregion

    #region Comparison methods

    /**
     * Check if this vector equals another.
     *
     * Two vectors are equal if they have the same size and all corresponding elements are exactly equal.
     * Returns false for non-Vector values.
     *
     * @param mixed $other The value to compare with.
     * @return bool True if the vectors are the same size and all elements are equal.
     */
    /** @disregard P1128 */
    #[Override]
    public function equal(mixed $other): bool
    {
        // Check both are Vector objects.
        if (!$other instanceof self) {
            return false;
        }

        // Check sizes are equal.
        if ($this->size !== $other->size) {
            return false;
        }

        // Check elements are equal.
        for ($i = 0; $i < $this->size; $i++) {
            if ($this->data[$i] !== $other->data[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this vector approximately equals another, within given tolerances.
     *
     * Each pair of corresponding elements is compared using Floats::approxEqual(), which checks
     * absolute tolerance first, then relative tolerance.
     *
     * @param mixed $other The value to compare with.
     * @param float $relTol The relative tolerance.
     * @param float $absTol The absolute tolerance.
     * @return bool True if the vectors are the same size and all elements are approximately equal.
     * @throws DomainException If either tolerance is negative.
     * @see Floats::approxEqual()
     */
    #[Override]
    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        // Check both are Vector objects.
        if (!$other instanceof self) {
            return false;
        }

        // Check sizes are equal.
        if ($this->size !== $other->size) {
            return false;
        }

        // Check elements are approximately equal.
        for ($i = 0; $i < $this->size; $i++) {
            if (!Floats::approxEqual($this->data[$i], $other->data[$i], $relTol, $absTol)) {
                return false;
            }
        }

        return true;
    }

    #endregion

    #region Unary arithmetic methods

    /**
     * Negate this vector.
     *
     * @return self A new vector with all elements negated.
     */
    public function neg(): self
    {
        return $this->mul(-1);
    }

    #endregion

    #region Binary arithmetic methods

    /**
     * Add another vector to this one.
     *
     * @param self $other Vector to add.
     * @return self New vector representing the sum.
     * @throws LengthException If vectors have different sizes.
     */
    public function add(self $other): self
    {
        // Check if vectors have the same size.
        if ($this->size !== $other->size) {
            throw new LengthException('Cannot add vectors of different sizes.');
        }

        // Add the vectors element-wise.
        $result = new self($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $result->set($i, $this->data[$i] + $other->data[$i]);
        }

        return $result;
    }

    /**
     * Subtract another vector from this one.
     *
     * @param self $other Vector to subtract.
     * @return self New vector representing the difference.
     * @throws LengthException If vectors have different sizes.
     */
    public function sub(self $other): self
    {
        // Check if vectors have the same size.
        if ($this->size !== $other->size) {
            throw new LengthException('Cannot subtract vectors of different sizes.');
        }

        // Subtract the vectors element-wise.
        $result = new self($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $result->set($i, $this->data[$i] - $other->data[$i]);
        }

        return $result;
    }

    /**
     * Multiply this vector by a scalar.
     *
     * @param float $scalar Number to multiply by.
     * @return self New vector representing the product.
     */
    public function mul(float $scalar): self
    {
        // Multiply the vectors element-wise.
        $result = new self($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $result->set($i, $this->data[$i] * $scalar);
        }

        return $result;
    }

    /**
     * Divide this vector by a scalar.
     *
     * @param float $scalar Number to divide by.
     * @return self New vector representing the quotient.
     * @throws DivisionByZeroError If scalar is zero.
     */
    public function div(float $scalar): self
    {
        // Guard.
        if (Numbers::isZero($scalar)) {
            throw new DivisionByZeroError('Cannot divide by zero.');
        }

        // Divide the vectors element-wise.
        $result = new self($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $result->set($i, $this->data[$i] / $scalar);
        }

        return $result;
    }

    #endregion

    #region Linear algebra methods

    /**
     * Calculate the dot product of this vector with another vector.
     *
     * @param self $other Vector to calculate dot product with.
     * @return float The dot product.
     * @throws LengthException If vectors have different sizes.
     */
    public function dot(self $other): float
    {
        // Check if vectors have the same size.
        if ($this->size !== $other->size) {
            throw new LengthException('Cannot compute dot product of vectors with different sizes.');
        }

        // Calculate the dot product element-wise.
        $result = 0.0;
        for ($i = 0; $i < $this->size; $i++) {
            $result += $this->data[$i] * $other->data[$i];
        }

        return $result;
    }

    /**
     * Calculate the cross product of this vector with another vector. Both must be size 3.
     *
     * @param self $other Vector to calculate cross product with.
     * @return self New vector representing the cross product.
     * @throws LengthException If either vector is not of size 3.
     */
    public function cross(self $other): self
    {
        // Check if vectors are size 3.
        if ($this->size !== 3) {
            throw new LengthException('Cannot compute cross product: first operand is not size 3.');
        }
        if ($other->size !== 3) {
            throw new LengthException('Cannot compute cross product: second operand is not size 3.');
        }

        return self::fromArray([
            $this->data[1] * $other->data[2] - $this->data[2] * $other->data[1],
            $this->data[2] * $other->data[0] - $this->data[0] * $other->data[2],
            $this->data[0] * $other->data[1] - $this->data[1] * $other->data[0],
        ]);
    }

    /**
     * Normalize this vector to a unit vector (magnitude 1).
     *
     * @return self A new vector with the same direction and magnitude 1.
     * @throws DivisionByZeroError If the vector has zero magnitude.
     */
    public function normalize(): self
    {
        assert($this->magnitude !== null);
        return $this->div($this->magnitude);
    }

    #endregion

    #region ArrayAccess methods

    /**
     * Check if an offset exists.
     *
     * @param mixed $offset Index to check.
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < $this->size;
    }

    /**
     * Get value at an offset.
     *
     * @param mixed $offset Index to get.
     * @return float
     * @throws OutOfRangeException If the offset is invalid.
     */
    public function offsetGet(mixed $offset): float
    {
        // Check offset exists.
        if (!$this->offsetExists($offset)) {
            throw new OutOfRangeException('Invalid offset: ' . Stringify::abbrev($offset) . '.');
        }
        assert(is_int($offset));

        return $this->get($offset);
    }

    /**
     * Set value at an offset.
     *
     * @param mixed $offset Index to set.
     * @param mixed $value Value to set.
     * @throws OutOfRangeException If offset is outside valid range.
     * @throws InvalidArgumentException If value is not a number.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Check offset exists.
        if (!$this->offsetExists($offset)) {
            throw new OutOfRangeException('Invalid offset: ' . Stringify::abbrev($offset) . '.');
        }
        assert(is_int($offset));

        // Check value is a number.
        if (!Numbers::isNumber($value)) {
            throw new InvalidArgumentException('Cannot use non-numeric elements in a vector.');
        }

        $this->set($offset, $value);
    }

    /**
     * Unset is not supported for vectors.
     *
     * @param mixed $offset Index.
     * @throws LogicException Always throws.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Cannot unset elements in a vector.');
    }

    #endregion
}
