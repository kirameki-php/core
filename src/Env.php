<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\KeyNotFoundException;
use Kirameki\Core\Exceptions\TypeMismatchException;
use function array_key_exists;
use function filter_var;
use function gettype;
use function is_null;
use function is_numeric;
use function ksort;
use function preg_match;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_INT;
use const INF;
use const NAN;

class Env extends StaticClass
{
    /**
     * Returns all environment variables.
     *
     * @param bool $sorted
     * [Optional] Sorts the result by key if **true**, otherwise
     * return in order it was inserted.
     * Defaults to **true**.
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
     * Returns the value of the environment variable as bool.
     * Throws `KeyNotFoundException` if the `$key` is not defined.
     * Throws `TypeMismatchException` if `$value` is not a valid bool.
     *
     * @param string $key
     * Key name of the environment variable.
     * @return bool
     */
    public static function getBool(string $key): bool
    {
        return self::getBoolOrNull($key)
            ?? self::throwKeyNotFoundException($key);
    }

    /**
     * Returns the value of the environment variable as bool.
     * Returns **null** if the `$key` is not defined.
     * Throws `TypeMismatchException` if `$value` is not a valid bool.
     *
     * @param string $key
     * Key name of the environment variable.
     * @return bool|null
     */
    public static function getBoolOrNull(string $key): ?bool
    {
        $value = self::getStringOrNull($key);

        return match ($value) {
            null => null,
            'true' => true,
            'false' => false,
            default => self::throwTypeMismatchException($key, $value, 'bool'),
        };
    }

    /**
     * Returns the value of the environment variable as int.
     * Throws `KeyNotFoundException` if the `$key` is not defined.
     * Throws `TypeMismatchException` if `$value` is not a valid int.
     *
     * @param string $key
     * Key name of the environment variable.
     * @return int
     */
    public static function getInt(string $key): int
    {
        return self::getIntOrNull($key)
            ?? self::throwKeyNotFoundException($key);
    }

    /**
     * Returns the value of the environment variable as int.
     * Returns **null** if the `$key` is not defined.
     * Throws `TypeMismatchException` if `$value` is not a valid int.
     *
     * @param string $key
     * Key name of the environment variable.
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
        self::throwTypeMismatchException($key, $value, 'int');
    }

    /**
     * Returns the value of the environment variable as float.
     * Throws `KeyNotFoundException` if the `$key` is not defined.
     * Throws `TypeMismatchException` if `$value` is not a valid float.
     *
     * @param string $key
     * Key name of the environment variable.
     * @return float
     */
    public static function getFloat(string $key): float
    {
        return self::getFloatOrNull($key)
            ?? self::throwKeyNotFoundException($key);
    }

    /**
     * Returns the value of the environment variable as float.
     * Returns **null** if the `$key` is not defined.
     * Throws `TypeMismatchException` if `$value` is not a valid float.
     *
     * @param string $key
     * Key name of the environment variable.
     * @return float|null
     */
    public static function getFloatOrNull(string $key): ?float
    {
        $value = self::getStringOrNull($key);
        if ($value === null) {
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
        self::throwTypeMismatchException($key, $value, 'float');
    }

    /**
     * Returns the value of the environment variable as string.
     * Throws `KeyNotFoundException` if the `$key` is not defined.
     *
     * @param string $key
     * Key name of the environment variable.
     * @return string
     */
    public static function getString(string $key): string
    {
        return self::getStringOrNull($key)
            ?? self::throwKeyNotFoundException($key);
    }

    /**
     * Returns the value of the environment variable as string.
     * Returns **null** if the `$key` is not defined.
     *
     * @param string $key
     * Key name of the environment variable.
     * @return string|null
     */
    public static function getStringOrNull(string $key): ?string
    {
        return $_ENV[$key] ?? null;
    }

    /**
     * Returns **true** if the environment variable exists, **false** otherwise.
     *
     * @param string $key
     * Key name of the environment variable.
     * @return bool
     */
    public static function exists(string $key): bool
    {
        return array_key_exists($key, $_ENV);
    }

    /**
     * @param string $key
     * Key name of the environment variable.
     * @return never-returns
     */
    private static function throwKeyNotFoundException(string $key): never
    {
        throw new KeyNotFoundException("ENV: {$key} is not defined.", [
            'key' => $key,
        ]);
    }

    /**
     * @param string $key
     * Key name of the environment variable.
     * @param mixed $value
     * Value of the environment variable.
     * @param string $expected
     * Name of the expected type.
     * @return never-returns
     */
    private static function throwTypeMismatchException(string $key, mixed $value, string $expected): never
    {
        $type = gettype($value);
        throw new TypeMismatchException("Expected: {$key} to be type {$expected}. Got: {$type}.", [
            'key' => $key,
            'value' => $value,
        ]);
    }
}
