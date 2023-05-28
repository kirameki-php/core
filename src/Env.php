<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\NotSupportedException;
use function filter_var;
use function gettype;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function is_numeric;
use function is_string;
use function ksort;
use function preg_match;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_INT;

final class Env
{
    /**
     * @return array<string, scalar>
     */
    public static function all(bool $sorted = true): array
    {
        $all = $_ENV;
        if ($sorted) {
            ksort($all);
        }
        return $all;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function getBool(string $key): bool
    {
        $value = self::getBoolOrNull($key);
        if ($value === null) {
            self::throwUndefinedException($key);
        }
        return $value;
    }

    /**
     * @param string $key
     * @return bool|null
     */
    public static function getBoolOrNull(string $key): ?bool
    {
        $value = self::getStringOrNull($key);
        if (is_null($value)) {
            return null;
        }
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        self::throwNotSupportedException($key, $value, 'bool');
    }

    /**
     * @param string $key
     * @return int
     */
    public static function getInt(string $key): int
    {
        $value = self::getIntOrNull($key);
        if ($value === null) {
            self::throwUndefinedException($key);
        }
        return $value;
    }

    /**
     * @param string $key
     * @return int|null
     */
    public static function getIntOrNull(string $key): ?int
    {
        $value = self::getStringOrNull($key);
        if (is_null($value)) {
            return null;
        }
        if (preg_match("/^-?([1-9][0-9]*|[0-9])$/", $value)) {
            return filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        }
        self::throwNotSupportedException($key, $value, 'int');
    }

    /**
     * @param string $key
     * @return float
     */
    public static function getFloat(string $key): float
    {
        $value = self::getFloatOrNull($key);
        if ($value === null) {
            self::throwUndefinedException($key);
        }
        return $value;
    }

    /**
     * @param string $key
     * @return float|null
     */
    public static function getFloatOrNull(string $key): ?float
    {
        $value = self::getStringOrNull($key);
        if (is_null($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if ($value === 'NAN') {
            return NAN;
        }
        if ($value === 'INF') {
            return INF;
        }
        if ($value === '-INF') {
            return -INF;
        }
        self::throwNotSupportedException($key, $value, 'float');
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getString(string $key): string
    {
        $value = self::getStringOrNull($key);
        if ($value === null) {
            self::throwUndefinedException($key);
        }
        return $value;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public static function getStringOrNull(string $key): ?string
    {
        return $_ENV[$key] ?? null;
    }

    /**
     * @param string $key
     * @param scalar $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_ENV[$key] = self::valueAsString($value);
    }

    /**
     * @param string $key
     * @param scalar $value
     * @return void
     */
    public static function setIfExists(string $key, mixed $value): void
    {
        if (self::exists($key)) {
            self::set($key, $value);
        }
    }

    /**
     * @param string $key
     * @param scalar $value
     * @return void
     */
    public static function setIfNotExists(string $key, mixed $value): void
    {
        if (!self::exists($key)) {
            self::set($key, $value);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool
    {
        return array_key_exists($key, $_ENV);
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function delete(string $key): bool
    {
        if (self::exists($key)) {
            unset($_ENV[$key]);
            return true;
        }
        return false;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function valueAsString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $type = gettype($value);
        throw new NotSupportedException("Type: {$type} cannot be converted to string.", [
            'value' => $value,
        ]);
    }

    /**
     * @param string $key
     * @return never-returns
     */
    private static function throwUndefinedException(string $key): never
    {
        throw new InvalidArgumentException("ENV: {$key} is not defined.");
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $expected
     * @return never-returns
     */
    private static function throwNotSupportedException(string $key, mixed $value, string $expected): never
    {
        $type = gettype($value);
        throw new NotSupportedException("Expected: {$key} to be type {$expected}. Got: {$type}.", [
            'key' => $key,
            'value' => $value,
        ]);
    }
}
