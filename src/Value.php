<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\InvalidTypeException;
use function class_exists;
use function explode;
use function interface_exists;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_iterable;
use function is_object;
use function is_scalar;
use function is_string;
use function substr;

final class Value extends StaticClass
{
    /**
     * Get the name of the type of `$value` as string.
     *
     * @param mixed $value
     * @return string
     */
    public static function getType(mixed $value): string
    {
        return get_debug_type($value);
    }

    /**
     * Check if `$value` matches the given type(s).
     *
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public static function isType(mixed $value, string $type): bool
    {
        if (!str_contains($type, '|')) {
            return self::checkIntersectionType($value, $type, $type, false);
        }

        foreach (explode('|', $type) as $uType) {
            if (self::checkIntersectionType($value, $uType, $type, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @param string $fullType
     * @param bool $requiresParentheses
     * @return bool
     */
    protected static function checkIntersectionType(
        mixed $value,
        string $type,
        string $fullType,
        bool $requiresParentheses,
    ): bool
    {
        if (!str_contains($type, '&')) {
            return self::checkSingleType($value, $type);
        }

        // unwrap parentheses
        if ($requiresParentheses) {
            if ($type[0] === '(' && $type[-1] === ')') {
                $type = substr($type, 1, -1);
            } else {
                throw new InvalidTypeException("Invalid Type: {$fullType} (Intersection type missing parentheses?)", [
                    'for' => $value,
                    'type' => $type,
                ]);
            }
        }

        foreach (explode('&', $type) as $iType) {
            if (!self::checkSingleType($value, $iType)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    protected static function checkSingleType(mixed $value, string $type): bool
    {
        if (class_exists($type) || interface_exists($type)) {
            // Check the inheritance chain for matches.
            return $value instanceof $type;
        }

        return match ($type) {
            'null' => $value === null,
            'bool' => is_bool($value),
            'int' => is_int($value),
            'float' => is_float($value),
            'string' => is_string($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'iterable' => is_iterable($value),
            'callable' => is_callable($value),
            'scalar' => is_scalar($value),
            'resource' => str_starts_with(get_debug_type($value), 'resource'),
            'mixed' => true,
            default => throw new InvalidTypeException('Invalid type: ' . $type, [
                'for' => $value,
                'type' => $type,
            ]),
        };
    }
}
