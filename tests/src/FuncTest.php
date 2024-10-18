<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Func;
use Kirameki\Core\Testing\TestCase;

final class FuncTest extends TestCase
{
    public function test_true(): void
    {
        $this->assertTrue(Func::true()());
    }

    public function test_false(): void
    {
        $this->assertFalse(Func::false()());
    }

    public function test_null(): void
    {
        $this->assertNull(Func::null()());
    }

    public function test_notNull(): void
    {
        $this->assertTrue(Func::notNull()(1));
        $this->assertFalse(Func::notNull()(null));
    }

    public function test_same(): void
    {
        $comparator = Func::same('foo');
        $this->assertTrue($comparator('foo'));
        $this->assertFalse($comparator('bar'));
    }

    public function test_notSame(): void
    {
        $comparator = Func::notSame('foo');
        $this->assertFalse($comparator('foo'));
        $this->assertTrue($comparator('bar'));
    }

    public function test_spaceship(): void
    {
        $comparator = Func::spaceship();
        $this->assertSame(-1, $comparator(1, 2));
        $this->assertSame(0, $comparator(2, 2));
        $this->assertSame(1, $comparator(2, 1));
    }
}
