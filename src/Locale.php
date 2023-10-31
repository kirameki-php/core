<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\NotSupportedException;
use ResourceBundle;
use function array_flip;
use function array_keys;

final class Locale extends StaticClass
{
    /**
     * @var string|null
     */
    private static ?string $current = null;

    /**
     * @var array<string, int>|null
     */
    private static ?array $cachedLocales = null;

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::resolveAvailable());
    }

    /**
     * @return string
     */
    public static function current(): string
    {
        return self::$current ?? 'en_US';
    }

    /**
     * @param string $locale
     * @return void
     */
    public static function set(string $locale): void
    {
        self::$current = self::ensureExists($locale);
    }

    /**
     * @param string $locale
     * @return bool
     */
    public static function exists(string $locale): bool
    {
        return isset(self::resolveAvailable()[$locale]);
    }

    /**
     * @param string $locale
     * @return string
     * @throws NotSupportedException
     */
    public static function ensureExists(string $locale): string
    {
        if (!self::exists($locale)) {
            throw new NotSupportedException("Locale: \"{$locale}\" does not exist.", [
                'locale' => $locale,
            ]);
        }
        return $locale;
    }

    /**
     * @return array<string, int>
     */
    private static function resolveAvailable(): array
    {
        if (self::$cachedLocales === null) {
            /** @var array<string, int> $list */
            $list = array_flip(ResourceBundle::getLocales(''));
            self::$cachedLocales = $list;
        }
        return self::$cachedLocales;
    }
}
