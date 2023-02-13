<?php declare(strict_types=1);

namespace Kirameki\Core;

use function is_int;
use function is_string;

/**
 * @param mixed $value
 * @return bool
 */
function is_array_key(mixed $value): bool
{
    return is_int($value) || is_string($value);
}

/**
 * @param mixed $value
 * @return bool
 */
function is_not_array_key(mixed $value): bool
{
    return ! is_array_key($value);
}
