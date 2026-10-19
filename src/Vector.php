<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use ArrayAccess;
use ArrayIterator;
use Countable;
use DomainException;
use InvalidArgumentException;
use IteratorAggregate;
use LengthException;
use LogicException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Stringify;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;
use OutOfRangeException;
use OverflowException;
use Override;
use Stringable;
use Traversable;

/**
 * Encapsulates a vector and provides a number of useful methods.
 *
 * @implements ArrayAccess<int, float>
 * @implements IteratorAggregate<int, float>
 */
final class Vector implements Stringable, Countable, ArrayAccess, IteratorAggregate
{
    use ApproxEquatable;

    #region Properties

    /**
     * The number of elements in the vector.
     */
    private(set) int $count;

    /**
     * The vector data.
     *
     * @var list<float>
     */
    private array $data;

    /**
     * Calculate the magnitude (norm) of the vector.
     *
     * The elements are scaled by a power of 2 before squaring, so the squares don't overflow to INF for a huge vector
     * or underflow to 0 for a tiny one.
     *
     * @throws OverflowException If the magnitude is too large to represent as a float.
     */
    public float $magnitude {
        get {
            // Handle a zero or empty vector.
            $scale = self::powerOf2Scale($this->data);
            if ($scale === 0.0) {
                return 0.0;
            }

            // Calculate the magnitude of the scaled vector, then undo the scaling. Square with * rather than ** 2,
            // which calls pow(), as pow() can be a unit in the last place off where * is always correctly rounded.
            $sum = 0.0;
            foreach ($this->data as $x) {
                $scaled = $x / $scale;
                $sum += $scaled * $scaled;
            }
            return self::checkResult($scale * sqrt($sum), 'compute magnitude of the given Vector');
        }
    }

    #endregion

    #region Constructor

    /**
     * Create a new vector with the specified count.
     *
     * @param int $count Number of elements.
     * @throws DomainException If count is negative.
     */
    public function __construct(int $count)
    {
        if ($count < 0) {
            throw new DomainException("Cannot create Vector with negative count: $count.");
        }

        $this->count = $count;
        $this->data = array_fill(0, $count, 0.0);
    }

    #endregion

    #region Factory methods

    /**
     * Create a vector from an array.
     *
     * @param array<array-key, mixed> $arr Array of numbers.
     * @return self
     * @throws InvalidArgumentException If an element isn't an int or float.
     * @throws DomainException If the array isn't a list, or an element isn't finite.
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
            throw new DomainException('Cannot create Vector from array. Must be a list.');
        }

        // Check all elements are finite numbers.
        foreach ($arr as $index => $value) {
            // Check the value is a number.
            if (!Numbers::isNumber($value)) {
                throw new InvalidArgumentException(
                    "Cannot create Vector from array. Element at index $index must be an int or float."
                );
            }

            // Check the value is finite.
            if (!is_finite($value)) {
                throw new DomainException(Stringify::prepEx(
                    "Cannot create Vector from array. Element at index $index (?) must be finite.",
                    $value
                ));
            }

            // Set the vector element.
            $data[] = Floats::normalizeZero((float) $value);
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
     * Convert this vector to a single-row Matrix.
     *
     * @return Matrix The matrix representation.
     */
    public function toRowMatrix(): Matrix
    {
        return Matrix::fromArray([
            $this->data,
        ]);
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
        $result = new Matrix($this->count, 1);
        for ($i = 0; $i < $this->count; $i++) {
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
            throw $this->indexException($index);
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
     * @throws DomainException If the value is not finite (±INF or NAN).
     */
    public function set(int $index, float $value): void
    {
        // Check index is valid.
        if ($index < 0 || $index >= count($this->data)) {
            throw $this->indexException($index);
        }

        // Check the value is finite.
        if (!is_finite($value)) {
            throw new DomainException(Stringify::prepEx('Cannot set element to non-finite value: ?.', $value));
        }

        $this->data[$index] = Floats::normalizeZero($value);
    }

    #endregion

    #region Comparison methods

    /**
     * Check if this Vector equals another.
     *
     * Two vectors are equal if they have the same count and all corresponding elements are exactly equal.
     *
     * @param mixed $other The value to compare with.
     * @return bool True if the vectors have the same count and all elements are equal.
     * @throws InvalidArgumentException If $other is not a Vector.
     */
    /** @disregard P1128 */
    #[Override] // Equatable
    public function equal(mixed $other): bool
    {
        // The argument has to be mixed to align with the trait method being overridden, so we add our own type check.
        // If we don't have a Vector, abort.
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare Vector with ' . get_debug_type($other) . '. Must be Vector.'
            );
        }

        // Check counts are equal.
        if ($this->count !== $other->count) {
            return false;
        }

        // Check elements are equal.
        for ($i = 0; $i < $this->count; $i++) {
            if ($this->data[$i] !== $other->data[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this vector approximately equals another, within given tolerances.
     *
     * Each pair of corresponding elements is compared using Floats::approxEqual(), which checks absolute tolerance
     * first, then relative tolerance.
     *
     * @param mixed $other The value to compare with.
     * @param float $relTol The relative tolerance.
     * @param float $absTol The absolute tolerance.
     * @return bool True if the vectors have the same count and all elements are approximately equal.
     * @throws InvalidArgumentException If $other is not a Vector.
     * @throws DomainException If either tolerance is negative.
     * @see Floats::approxEqual()
     */
    #[Override] // ApproxEquatable
    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        // The argument has to be mixed to align with the trait method being overridden, so we add our own type check.
        // If we don't have a Vector, abort.
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare Vector with ' . get_debug_type($other) . '. Must be Vector.'
            );
        }

        // Check counts are equal.
        if ($this->count !== $other->count) {
            return false;
        }

        // Check elements are approximately equal.
        for ($i = 0; $i < $this->count; $i++) {
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
        $result = new self($this->count);
        for ($i = 0; $i < $this->count; $i++) {
            $result->set($i, -$this->data[$i]);
        }

        return $result;
    }

    /**
     * Calculate the element-wise reciprocal of this vector.
     *
     * @return self A new vector with each element replaced by its reciprocal.
     * @throws ArithmeticException If any element is zero.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function reciprocal(): self
    {
        $data = [];
        for ($i = 0; $i < $this->count; $i++) {
            if ($this->data[$i] === 0.0) {
                throw new ArithmeticException("Cannot compute reciprocal of zero at index $i.");
            }
            $data[] = self::checkResult(1 / $this->data[$i], 'compute reciprocal of the given Vector');
        }

        return self::fromList($data);
    }

    #endregion

    #region Binary arithmetic methods

    /**
     * Add another vector to this one.
     *
     * @param self $other Vector to add.
     * @return self New vector representing the sum.
     * @throws LengthException If vectors have different counts.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function add(self $other): self
    {
        // Check if vectors have the same count.
        if ($this->count !== $other->count) {
            throw new LengthException(
                "Cannot add Vector of incorrect count: {$other->count}. Expected {$this->count}."
            );
        }

        // Add the vectors element-wise.
        $data = [];
        for ($i = 0; $i < $this->count; $i++) {
            $data[] = self::checkResult($this->data[$i] + $other->data[$i], 'add the given Vectors');
        }

        return self::fromList($data);
    }

    /**
     * Subtract another vector from this one.
     *
     * @param self $other Vector to subtract.
     * @return self New vector representing the difference.
     * @throws LengthException If vectors have different counts.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function sub(self $other): self
    {
        // Check if vectors have the same count.
        if ($this->count !== $other->count) {
            throw new LengthException(
                "Cannot subtract Vector of incorrect count: {$other->count}. Expected {$this->count}."
            );
        }

        // Subtract the vectors element-wise.
        $data = [];
        for ($i = 0; $i < $this->count; $i++) {
            $data[] = self::checkResult($this->data[$i] - $other->data[$i], 'subtract the given Vectors');
        }

        return self::fromList($data);
    }

    /**
     * Multiply this vector by a scalar, another vector, or a matrix.
     *
     * Multiplying by a vector is element-wise (the Hadamard product); the vectors must have the same count.
     *
     * Multiplying by a matrix (_xA_) treats this vector as a row vector; its count must equal the matrix's row count.
     * To go the other way (_Ax_), call {@see Matrix::mul()}.
     *
     * @param float|self|Matrix $other Number, vector, or matrix to multiply by.
     * @return self New vector representing the product.
     * @throws DomainException If $other is a float that isn't finite (±INF or NAN).
     * @throws LengthException If the vectors have different counts, or the matrix's row count doesn't equal this
     * vector's count.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function mul(float|self|Matrix $other): self
    {
        // Multiply vector by a float.
        if (is_float($other)) {
            // Check the scalar is finite.
            if (!is_finite($other)) {
                throw new DomainException(Stringify::prepEx('Invalid scalar: ?. Must be finite.', $other));
            }

            // Multiply each element by the scalar.
            $operation = Stringify::prepEx('multiply the given Vector by ?', $other);
            $data = [];
            for ($i = 0; $i < $this->count; $i++) {
                $data[] = self::checkResult($this->data[$i] * $other, $operation);
            }

            return self::fromList($data);
        }

        // Multiply vector by a vector, element-wise.
        if ($other instanceof self) {
            if ($this->count !== $other->count) {
                throw new LengthException(
                    "Cannot multiply by Vector of incorrect count: {$other->count}. Expected {$this->count}."
                );
            }

            $data = [];
            for ($i = 0; $i < $this->count; $i++) {
                $data[] = self::checkResult($this->data[$i] * $other->data[$i], 'multiply the given Vectors');
            }

            return self::fromList($data);
        }

        // Multiply vector by a matrix: xA, treating this vector as a row vector.
        if ($this->count !== $other->rowCount) {
            throw new LengthException(
                "Invalid Matrix row count: {$other->rowCount}. Must equal this Vector's count: {$this->count}."
            );
        }

        // Calculate each element of the result as the dot product of this vector with a column of the matrix.
        $rows = $other->toArray();
        $data = [];
        for ($j = 0; $j < $other->columnCount; $j++) {
            $sum = 0.0;
            for ($i = 0; $i < $this->count; $i++) {
                $sum += $this->data[$i] * $rows[$i][$j];
            }
            $data[] = self::checkResult($sum, 'multiply the given Vector and Matrix');
        }

        return self::fromList($data);
    }

    /**
     * Divide this vector by a scalar or another vector.
     *
     * Dividing by a vector is element-wise (Hadamard division); the vectors must have the same count.
     *
     * @param float|self $other Number or vector to divide by.
     * @return self New vector representing the quotient.
     * @throws DomainException If $other is a float that isn't finite (±INF or NAN).
     * @throws ArithmeticException If $other is zero, or a vector with a zero element.
     * @throws LengthException If the vectors have different counts.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function div(float|self $other): self
    {
        // Divide vector by a vector, element-wise.
        if ($other instanceof self) {
            if ($this->count !== $other->count) {
                throw new LengthException(
                    "Cannot divide by Vector of incorrect count: {$other->count}. Expected {$this->count}."
                );
            }

            $data = [];
            for ($i = 0; $i < $this->count; $i++) {
                if ($other->data[$i] === 0.0) {
                    throw new ArithmeticException("Cannot divide by zero at index $i.");
                }
                $data[] = self::checkResult($this->data[$i] / $other->data[$i], 'divide the given Vectors');
            }

            return self::fromList($data);
        }

        // Check the scalar is finite. Dividing by ±INF would otherwise silently give a zero vector.
        if (!is_finite($other)) {
            throw new DomainException(Stringify::prepEx('Invalid scalar: ?. Must be finite.', $other));
        }

        // Guard.
        if ($other === 0.0) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        // Divide each element by the scalar.
        $operation = Stringify::prepEx('divide the given Vector by ?', $other);
        $data = [];
        for ($i = 0; $i < $this->count; $i++) {
            $data[] = self::checkResult($this->data[$i] / $other, $operation);
        }

        return self::fromList($data);
    }

    #endregion

    #region Linear algebra methods

    /**
     * Calculate the dot product of this vector with another vector.
     *
     * @param self $other Vector to calculate dot product with.
     * @return float The dot product.
     * @throws LengthException If vectors have different counts.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function dot(self $other): float
    {
        // Check if vectors have the same count.
        if ($this->count !== $other->count) {
            throw new LengthException(
                "Cannot compute dot product with Vector of incorrect count: {$other->count}. Expected {$this->count}."
            );
        }

        // Calculate the dot product element-wise.
        $result = 0.0;
        for ($i = 0; $i < $this->count; $i++) {
            $result += $this->data[$i] * $other->data[$i];
        }

        return self::checkResult($result, 'compute dot product of the given Vectors');
    }

    /**
     * Calculate the cross product of this vector with another vector. Both must have a count of 3.
     *
     * If an intermediate product overflows, the vectors are scaled by powers of 2 (which is exact) and the cross
     * product is calculated again. So a result that fits is still returned, e.g. a huge vector crossed with itself
     * gives zero.
     *
     * @param self $other Vector to calculate cross product with.
     * @return self New vector representing the cross product.
     * @throws LengthException If either vector's count isn't 3.
     * @throws OverflowException If the result is too large to represent as floats.
     */
    public function cross(self $other): self
    {
        // Check if vectors have a count of 3.
        if ($this->count !== 3) {
            throw new LengthException(
                'Cannot compute cross product with first Vector count: ' . $this->count . '. Must be 3.'
            );
        }
        if ($other->count !== 3) {
            throw new LengthException(
                'Cannot compute cross product with second Vector count: ' . $other->count . '. Must be 3.'
            );
        }

        // Calculate the cross product directly, which is exact for ordinary values.
        $a = $this->data;
        $b = $other->data;
        $result = self::calcCross($a, $b);

        // If an intermediate product overflowed, scale each vector so its largest element is in the range [1, 2), and
        // try again. Scaling by a power of 2 is exact. Neither vector can be zero here, or every product would be 0.
        if (!self::allFinite($result)) {
            $scaleA = self::powerOf2Scale($a);
            $scaleB = self::powerOf2Scale($b);
            $result = self::calcCross(
                array_map(static fn ($x) => $x / $scaleA, $a),
                array_map(static fn ($x) => $x / $scaleB, $b)
            );

            // Undo the scaling, applying the smaller scale first, so only a genuine overflow gives INF.
            $minScale = min($scaleA, $scaleB);
            $maxScale = max($scaleA, $scaleB);
            $result = array_map(static fn ($x) => $x * $minScale * $maxScale, $result);
            if (!self::allFinite($result)) {
                throw new OverflowException(
                    'Cannot compute cross product of the given Vectors. The result would overflow.'
                );
            }
        }

        $vector = new self(3);
        $vector->data = array_map(Floats::normalizeZero(...), $result);
        return $vector;
    }

    /**
     * Calculate the outer product of this vector with another vector.
     *
     * Unlike dot() and cross(), the vectors don't need to be the same count - the result is always an m×n Matrix,
     * where m is this vector's count and n is $other's count.
     *
     * @param self $other Vector to calculate outer product with.
     * @return Matrix New matrix representing the outer product.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function outer(self $other): Matrix
    {
        // Treat $this vector as a column matrix and $other as a row matrix, then multiply.
        try {
            return $this->toColumnMatrix()->mul($other->toRowMatrix());
        } catch (OverflowException $e) {
            throw new OverflowException(
                'Cannot compute outer product of the given Vectors. The result would overflow.',
                0,
                $e
            );
        }
    }

    /**
     * Normalize this vector to a unit vector (magnitude 1).
     *
     * The vector is first scaled by a power of 2 (which is exact), so its magnitude can't overflow to INF even when the
     * vector's true magnitude is too large to represent.
     *
     * @return self A new vector with the same direction and magnitude 1.
     * @throws ArithmeticException If the vector has zero magnitude.
     */
    public function normalize(): self
    {
        // Check for a zero (or empty) vector.
        $scale = self::powerOf2Scale($this->data);
        if ($scale === 0.0) {
            throw new ArithmeticException('Cannot normalize zero Vector.');
        }

        // Scale so the largest element is in the range [1, 2), then divide by the magnitude, which can't overflow.
        $scaled = $this->div($scale);
        return $scaled->div($scaled->magnitude);
    }

    #endregion

    #region Aggregation methods

    /**
     * Calculate the sum of all elements in the vector.
     *
     * @return float The sum. 0.0 for an empty vector (the additive identity).
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function sum(): float
    {
        return self::checkResult(array_sum($this->data), 'compute sum of the given Vector');
    }

    /**
     * Calculate the product of all elements in the vector.
     *
     * @return float The product. 1.0 for an empty vector (the multiplicative identity).
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function prod(): float
    {
        return self::checkResult(array_product($this->data), 'compute product of the given Vector');
    }

    /**
     * Get the number of elements in the vector.
     *
     * @return int
     */
    #[Override] // Countable
    public function count(): int
    {
        return $this->count;
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
        return is_int($offset) && $offset >= 0 && $offset < $this->count;
    }

    /**
     * Get value at an offset.
     *
     * @param mixed $offset Index to get.
     * @return float
     * @throws InvalidArgumentException If the offset is not an int.
     * @throws OutOfRangeException If the offset is outside the valid range.
     */
    #[Override] // ArrayAccess
    public function offsetGet(mixed $offset): float
    {
        // Check index type.
        if (!is_int($offset)) {
            throw new InvalidArgumentException('Invalid index type: ' . get_debug_type($offset) . '. Must be int.');
        }

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
        // Check index type.
        if (!is_int($offset)) {
            throw new InvalidArgumentException('Invalid index type: ' . get_debug_type($offset) . '. Must be int.');
        }

        // Check element type.
        if (!Numbers::isNumber($value)) {
            throw new InvalidArgumentException(
                'Invalid element type: ' . get_debug_type($value) . '. Must be int or float.'
            );
        }

        $this->set($offset, $value);
    }

    /**
     * Unset is not supported for Vector, which has a fixed count and contains only floats.
     *
     * @param mixed $offset Index.
     * @throws LogicException Always throws.
     */
    #[Override] // ArrayAccess
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Cannot unset Vector elements.');
    }

    #endregion

    #region IteratorAggregate implementation

    /**
     * Get an iterator over the elements, for foreach loops.
     *
     * The elements are a snapshot: changing the Vector during the loop doesn't change the values still to come.
     *
     * @return Traversable<int, float> The iterator.
     */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    #endregion

    #region Helper methods

    /**
     * Create the exception for an index outside the valid range.
     *
     * The callers check the index inline, against count($this->data), so PHPStan knows a valid index exists in the
     * list.
     *
     * @param int $index The invalid index.
     * @return OutOfRangeException The exception, naming the valid range, or saying the Vector is empty.
     */
    private function indexException(int $index): OutOfRangeException
    {
        return new OutOfRangeException(
            "Invalid index: $index. "
            . ($this->count === 0 ? 'The Vector is empty.' : 'Must be in the range 0-' . ($this->count - 1) . '.')
        );
    }

    /**
     * Create a vector from a list of finite floats, without validating them.
     *
     * @param list<float> $data The elements.
     * @return self The new vector.
     */
    private static function fromList(array $data): self
    {
        $vector = new self(count($data));
        $vector->data = $data;
        return $vector;
    }

    /**
     * Check a calculated value is representable, and normalize a negative zero.
     *
     * With finite operands, a non-finite result means it overflowed. A NAN result means an intermediate value
     * overflowed (e.g. INF - INF or 0 × INF), so it's reported as an overflow too.
     *
     * @param float $value The calculated value.
     * @param string $operation The operation, for the exception message, e.g. 'add the given Vectors'.
     * @return float The value, with -0.0 replaced by 0.0.
     * @throws OverflowException If the value is not finite.
     */
    private static function checkResult(float $value, string $operation): float
    {
        if (!is_finite($value)) {
            throw new OverflowException("Cannot $operation. The result would overflow.");
        }

        return Floats::normalizeZero($value);
    }

    /**
     * Get the power of 2 that scales the largest absolute value into the range [1, 2).
     *
     * Dividing by a power of 2 is exact, so it can be used to keep intermediate values (e.g. squares) in range
     * without changing the result.
     *
     * @param list<float> $values The values.
     * @return float The power of 2, or 0.0 if every value is zero (or there are none).
     */
    private static function powerOf2Scale(array $values): float
    {
        $max = 0.0;
        foreach ($values as $value) {
            $max = max($max, abs($value));
        }

        if ($max === 0.0) {
            return 0.0;
        }

        // log() can round up to the next power of 2, e.g. to 1024 near PHP_FLOAT_MAX, where 2 ** 1024 is INF. If so,
        // use the power below.
        $exp = floor(log($max, 2));
        if (2 ** $exp > $max) {
            $exp--;
        }
        return 2 ** $exp;
    }

    /**
     * Calculate the cross product of two 3-element arrays, without overflow checks.
     *
     * @param list<float> $a The first vector's elements.
     * @param list<float> $b The second vector's elements.
     * @return list<float> The cross product's elements, which may include ±INF or NAN.
     */
    private static function calcCross(array $a, array $b): array
    {
        return [
            $a[1] * $b[2] - $a[2] * $b[1],
            $a[2] * $b[0] - $a[0] * $b[2],
            $a[0] * $b[1] - $a[1] * $b[0],
        ];
    }

    /**
     * Check every value in an array is finite.
     *
     * @param list<float> $values The values to check.
     * @return bool True if every value is finite.
     */
    private static function allFinite(array $values): bool
    {
        return array_all($values, static fn ($x) => is_finite($x));
    }

    #endregion
}
