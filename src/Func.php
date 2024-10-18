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
    public static function notNull(): Closure
    {
        return static fn(mixed $v): bool => $v !== null;
    }

    /**
     * @return Closure(mixed): bool
     */
    public static function same(mixed $value): Closure
    {
        return static fn(mixed $v): bool => $v === $value;
    }

    /**
     * @return Closure(mixed): bool
     */
    public static function notSame(mixed $value): Closure
    {
        return static fn(mixed $v): bool => $v !== $value;
    }

    /**
     * @return Closure(mixed, mixed): int
     */
    public static function spaceship(): Closure
    {
        return static fn(mixed $a, mixed $b): int => $a <=> $b;
    }
}
