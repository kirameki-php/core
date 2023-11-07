<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Locale;
use Kirameki\Core\Testing\TestCase;

final class LocaleTest extends TestCase
{
    public function test_all(): void
    {
        $all = Locale::all();
        self::assertContains('en', $all);
        self::assertContains('en_US', $all);
    }

    public function test_set(): void
    {
        $default = Locale::current();
        Locale::set('en_GB');

        try {
            self::assertSame('en_GB', Locale::current());
        }
        finally {
            Locale::set($default);
        }
    }

    public function test_current(): void
    {
        self::assertSame('en_US', Locale::current());
    }

    public function test_exists(): void
    {
        self::assertTrue(Locale::exists('en'));
        self::assertTrue(Locale::exists('en_US'));
        self::assertFalse(Locale::exists('en_none'));
    }

    public function test_ensureExists(): void
    {
        $this->expectExceptionMessage('Locale: "none" does not exist.');
        $this->expectException(NotSupportedException::class);
        Locale::ensureExists('none');
    }
}
