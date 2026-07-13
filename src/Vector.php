<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use ArrayAccess;
use Countable;
use DivisionByZeroError;
use DomainException;
use InvalidArgumentException;
use LengthException;
use LogicException;
use OceanMoon\Core\Exceptions\ConversionException;
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
final class Vector implements Stringable, Countable, ArrayAccess
{
    use ApproxEquatable;

    #region Properties

    #region Private properties

    /**
     * The vector data.
     *
     * @var list<float>
     */
    private array $data;

    #endregion

    #region Public properties (readonly)

    /**
     * The number of elements in the vector.
     */
    private(set) int $size;

    #endregion

    #region Public properties (computed, readonly)

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
            throw new DomainException("Cannot create a Vector with negative size: $size.");
        }

        $this->size = $size;
        $this->data = array_fill(0, $size, 0.0);
    }

    #endregion

    #region Factory methods

    /**
     * Create a vector from an array.
     *
     * @param array<array-key, mixed> $arr Array of numbers.
     * @return self
     * @throws ConversionException If the array could not be converted to a Vector.
     */
    public static function fromArray(array $arr): self
    {
        // Handle empty input.
        if (empty($arr)) {
            return new self(0);
        }

        $data = [];

        // Check for list.
        if (!array_is_list($arr)) {
            throw new ConversionException($arr, self::class, 'Array must be a list.');
        }

        // Check all elements are numbers.
        foreach ($arr as $value) {
            // Check if the value is a number.
            if (!Numbers::isNumber($value)) {
                throw new ConversionException($arr, self::class, 'All elements must be numbers.');
            }

            // Convert the value to a float.
            $data[] = (float) $value;
        }

        // Create the Vector.
        $vector = new self(count($data));
        $vector->data = $data;

        return $vector;
    }

    /**
     * Convert the input value to a Vector, if not already, and if possible.
     *
     * A Matrix can be converted to a Vector only if it has exactly 1 column, matching the
     * column-vector convention used elsewhere (e.g. Matrix::toMatrix(), Matrix::mul()).
     *
     * @param mixed $value The value to convert.
     * @return self The equivalent Vector.
     * @throws ConversionException If the value cannot be converted to a Vector.
     */
    public static function toVector(mixed $value): self
    {
        // Check for Vector.
        if ($value instanceof self) {
            return $value;
        }

        // Check for array and convert to Vector if possible.
        if (is_array($value)) {
            return self::fromArray($value);
        }

        // Check for Matrix with exactly one column.
        if ($value instanceof Matrix) {
            // If the Matrix has only one column, get the column as a Vector.
            if ($value->columnCount === 1) {
                return $value->getColumn(0);
            }
            throw new ConversionException($value, self::class, 'Matrix must have exactly one column.');
        }

        // The value has a type that cannot be converted to Vector.
        throw new ConversionException($value, self::class);
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
     * Convert this vector to a single-row Matrix.
     *
     * @return Matrix The matrix representation.
     */
    public function toRowMatrix(): Matrix
    {
        return Matrix::fromArray([$this->data]);
    }

    /**
     * Convert this vector to a single-column Matrix.
     *
     * Built directly rather than via Matrix::fromArray(), because fromArray([]) can't distinguish a
     * 0×0 matrix from a 0×1 one: for an empty vector, array_map() over $this->data would produce [],
     * which fromArray() treats as its 0×0 empty-matrix shortcut. Constructing directly guarantees this
     * always returns an n×1 matrix, even when n is 0.
     *
     * @return Matrix The matrix representation.
     */
    public function toColumnMatrix(): Matrix
    {
        $result = new Matrix($this->size, 1);
        for ($i = 0; $i < $this->size; $i++) {
            $result->set($i, 0, $this->data[$i]);
        }
        return $result;
    }

    /**
     * Convert the vector to a string representation.
     * This format uses ordered tuple notation and mathematical angle brackets.
     *
     * @return string String representation of the Vector.
     */
    #[Override] // Stringable
    public function __toString(): string
    {
        return '⟨' . implode(', ', $this->data) . '⟩';
    }

    #endregion

    #region Inspection methods

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

    #endregion

    #region Modification methods

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
     * Check if this vector equals another value, which may be Vector, an array of numbers, or a single-column
     * Matrix; i.e. anything that can be accepted by toVector().
     *
     * Two vectors are equal if they have the same size and all corresponding elements are exactly equal.
     *
     * @param mixed $other The value to compare with.
     * @return bool True if the vectors are the same size and all elements are equal.
     * @throws ConversionException If the value cannot be converted to a Vector.
     */
    /** @disregard P1128 */
    #[Override] // Equatable
    public function equal(mixed $other): bool
    {
        // Get other value as a Vector.
        $other = self::toVector($other);

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
     * Check if this vector approximately equals another value, within given tolerances. The other value may be
     * Vector, an array of numbers, or a single-column Matrix; i.e. anything that can be accepted by toVector().
     *
     * Each pair of corresponding elements is compared using Floats::approxEqual(), which checks
     * absolute tolerance first, then relative tolerance.
     *
     * @param mixed $other The value to compare with.
     * @param float $relTol The relative tolerance.
     * @param float $absTol The absolute tolerance.
     * @return bool True if the vectors are the same size and all elements are approximately equal.
     * @throws ConversionException If the value cannot be converted to a Vector.
     * @throws DomainException If either tolerance is negative.
     * @see Floats::approxEqual()
     */
    #[Override] // ApproxEquatable
    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        // Get other value as a Vector.
        $other = self::toVector($other);

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

    /**
     * Calculate the Hadamard product (element-wise product) of this vector with another.
     *
     * @param self $other Vector to multiply element-wise with.
     * @return self New vector representing the Hadamard product.
     * @throws LengthException If vectors have different sizes.
     */
    public function hadamard(self $other): self
    {
        // Check if vectors have the same size.
        if ($this->size !== $other->size) {
            throw new LengthException('Cannot compute Hadamard product of vectors with different sizes.');
        }

        // Multiply the vectors element-wise.
        $result = new self($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $result->set($i, $this->data[$i] * $other->data[$i]);
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

    #region Aggregation methods

    /**
     * Calculate the sum of all elements in the vector.
     *
     * @return float The sum. 0.0 for an empty vector (the additive identity).
     */
    public function sum(): float
    {
        return array_sum($this->data);
    }

    /**
     * Calculate the product of all elements in the vector.
     *
     * @return float The product. 1.0 for an empty vector (the multiplicative identity).
     */
    public function prod(): float
    {
        return array_product($this->data);
    }

    /**
     * Get the number of elements in the vector.
     *
     * @return int
     */
    #[Override] // Countable
    public function count(): int
    {
        return $this->size;
    }

    #endregion

    #region ArrayAccess methods

    /**
     * Check if an offset exists.
     *
     * @param mixed $offset Index to check.
     * @return bool
     */
    #[Override] // ArrayAccess
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
    #[Override] // ArrayAccess
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
    #[Override] // ArrayAccess
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
    #[Override] // ArrayAccess
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Cannot unset elements in a vector.');
    }

    #endregion
}
