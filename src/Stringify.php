<?php

declare(strict_types = 1);

namespace Galaxon\Math;

use ValueError;
use JsonException;
use Stringable;
use TypeError;

/**
 * This class provides a conversion of PHP values to strings with a few differences from the default options:
 * 1. Floats never look like integers.
 * 2. Arrays that are lists will not show keys (like JSON arrays).
 * 3. Objects will be converted using their __toString() method if implemented, otherwise as an HTML tag, with bonus
 *    UML-style visibility modifiers.
 * 4. Resources are encoded like HTML tags.
 *
 * The purpose of the class is to offer a somewhat more readable alternative to var_dump(), var_export(), print_r(),
 * json_encode(), or serialize(). Useful for error and log messages.
 */
class Stringify
{
    /**
     * Convert a value to a readable string representation.
     *
     * @param mixed $value The value to encode.
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the value.
     * @throws ValueError If the value cannot be stringified.
     * @throws TypeError If the value has an unknown type.
     */
    public static function stringify(mixed $value, bool $pretty_print = false, int $indent_level = 0): string
    {
        // Check for object.
        if (is_object($value)) {
            return self::stringifyObject($value, $pretty_print, $indent_level);
        }

        // Get the type.
        $type = get_debug_type($value);

        // Call the relevant encode method.
        switch ($type) {
            case 'null':
            case 'bool':
            case 'int':
            case 'string':
                // Use a try-catch here to silence the IDE, but none of these types will throw.
                try {
                    $json = json_encode($value, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new ValueError("Value cannot be encoded as JSON: " . $e->getMessage());
                }
                return $json;

            case 'float':
                return self::stringifyFloat($value);

            case 'array':
                return self::stringifyArray($value, $pretty_print, $indent_level);
        }

        // Check for resource.
        if (str_starts_with($type, 'resource')) {
            return self::stringifyResource($value);
        }

        // Not sure if this can ever actually happen. gettype() can return 'unknown type' but
        // get_debug_type() has no equivalent.
        throw new TypeError("Key has unknown type.");
    }

    /**
     * Encode a float in such a way that it doesn't look like an integer.
     *
     * @param float $value The float value to encode.
     * @return string The string representation of the float.
     */
    public static function stringifyFloat(float $value): string
    {
        // Handle special values.
        if (is_nan($value)) {
            return 'NaN';
        }
        if ($value === INF) {
            return '∞';
        }
        if ($value === -INF) {
            return '-∞';
        }

        // Convert the float to a string.
        $s = (string)$value;
        // If the string representation of the float value has no decimal point or exponent (i.e. nothing to distinguish
        // it from an integer), append a decimal point.
        if (!preg_match('/[.eE]/', $s)) {
            $s .= '.0';
        }
        return $s;
    }

    /**
     * Encode a PHP array as sequence enclosed by square brackets.
     *
     * A list (i.e. an array with sequential integer keys starting at 0) will show values only, and an associative
     * array will show key-value pairs.
     *
     * @param array $ary The array to encode.
     * @param bool $pretty_print Whether to use pretty printing (default false).
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the array.
     * @throws ValueError If the array contains circular references.
     */
    public static function stringifyArray(array $ary, bool $pretty_print = false, int $indent_level = 0): string
    {
        // Detect circular references.
        try {
            json_encode($ary, JSON_THROW_ON_ERROR);
        }
        catch (JsonException) {
            throw new ValueError("Cannot stringify arrays containing circular references.");
        }

        $pairs = [];
        $indent = $pretty_print ? str_repeat(' ', 4 * ($indent_level + 1)) : '';
        $is_list = array_is_list($ary);

        foreach ($ary as $key => $value) {
            $value_str = self::stringify($value, $pretty_print, $indent_level + 1);
            // Encode a list without no keys.
            if ($is_list) {
                $pairs[] = $indent . $value_str;
            }
            else {
                // Encode an associative array with keys.
                $key_str = self::stringify($key, $pretty_print, $indent_level + 1);
                $pairs[] = $indent . $key_str . ' => ' . $value_str;
            }
        }

        // If pretty print, return string formatted with new lines and indentation.
        if ($pretty_print) {
            $brace_indent = str_repeat(' ', 4 * $indent_level);
            return "[\n" . implode(",\n", $pairs) . "\n$brace_indent]";
        }

        return '[' . implode(', ', $pairs) . ']';
    }

    /**
     * Stringify a resource. It looks a bit like an HTML tag.
     *
     * @param mixed $value The resource to stringify.
     * @return string The string representation of the resource.
     * @throws TypeError If the value is not a resource.
     */
    public static function stringifyResource(mixed $value): string {
        // Can't type hint for resource, so check manually.
        if (!is_resource($value)) {
            throw new TypeError("Value is not a resource.");
        }

        return '<resource type = "' . get_resource_type($value) . '", id = ' . get_resource_id($value) . '>';
    }

    /**
     * Convert an object to a string.
     *
     * The result looks similar to an HTML tag, except that:
     * - the fully qualified class name is used (with the namespace)
     * - key-value pairs are comma-separated
     * - the visibility of each property is shown using UML notation
     *
     * @param object $obj The object to encode.
     * @param bool $pretty_print Whether to use pretty printing (default false).
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the object.
     * @throws TypeError If the object's class is anonymous.
     */
    public static function stringifyObject(object $obj, bool $pretty_print = false, int $indent_level = 0): string
    {
        // Get the tag name.
        $class = get_class($obj);

        // Check for anonymous classes.
        if (str_contains($class, '@anonymous')) {
            throw new TypeError("Cannot stringify anonymous classes.");
        }

        // Convert the object to an array to get its properties.
        // This works better than reflection, as new properties can be created when converting the object to an array
        // (example: DateTime).
        $a = (array)$obj;

        // Early return if no properties.
        if (count($a) === 0) {
            return "<$class>";
        }

        // Generate the strings for key-value pairs. Each will be on its own line.
        $pairs = [];
        $indent = $pretty_print ? str_repeat(' ', 4 * ($indent_level + 1)) : '';

        foreach ($a as $key => $value) {
            // Split on null bytes to determine the property name and visibility.
            $name_parts = explode("\0", $key);
            switch (count($name_parts)) {
                case 1:
                    $vis_symbol = '+';
                    break;

                case 3:
                    $vis_symbol = $name_parts[1] === '*' ? '#' : '-';
                    $key = $name_parts[2];
                    break;

                default:
                    // If there are 4 parts, the object is an anonymous class with a property
                    // indicating where the class is defined. We don't care about that, so ignore it.
                    // We already blocked anonymous classes above, so this should never happen.
                    continue 2;
            }

            $pairs[] = $indent . $vis_symbol . $key . ' = ' . self::stringify($value, $pretty_print, $indent_level + 1);
        }

        // If pretty print, return string formatted with new lines and indentation.
        if ($pretty_print) {
            return "<$class\n" . implode(",\n", $pairs) . '>';
        }

        return "<$class " . implode(', ', $pairs) . '>';
    }

    /**
     * Get a short string representation of the given value for use in error messages, log messages, and the like.
     *
     * @param mixed $value The value to get the string representation for.
     * @param int $max_len The maximum length of the result.
     * @return string The short string representation.
     */
    public static function abbrev(mixed $value, int $max_len = 20): string {
        // Get the value as a string without newlines or indentation.
        $result = self::stringify($value);

        // Trim if necessary.
        if ($max_len > 4 && strlen($result) > $max_len) {
            $result = substr($result, 0, $max_len - 3) . '...';
        }

        return $result;
    }
}
