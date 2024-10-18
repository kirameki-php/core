<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;

final class Func extends StaticClass
{
    /**
     * @return Closure(): bool
     */
    public static function true(): Closure
    {
        return static fn(): bool => true;
    }

    /**
     * @return Closure(): bool
     */
    public static function false(): Closure
    {
        return static fn(): bool => false;
    }

    /**
     * @return Closure(): null
     */
    public static function null(): Closure
    {
        return static fn(): null => null;
    }

    /**
     * @return Closure(mixed): bool
     */
    public static function match(mixed $value): Closure
    {
        return static fn(mixed $v): bool => $v === $value;
    }

    /**
     * @return Closure(mixed): bool
     */
    public static function notMatch(mixed $value): Closure
    {
        return static fn(mixed $v): bool => $v !== $value;
    }
}
