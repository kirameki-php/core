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

    public function test_match(): void
    {
        $matcher = Func::match('foo');
        $this->assertTrue($matcher('foo'));
        $this->assertFalse($matcher('bar'));
    }

    public function test_notMatch(): void
    {
        $matcher = Func::notMatch('foo');
        $this->assertFalse($matcher('foo'));
        $this->assertTrue($matcher('bar'));
    }

    public function test_spaceship(): void
    {
        $comparator = Func::spaceship();
        $this->assertSame(-1, $comparator(1, 2));
        $this->assertSame(0, $comparator(2, 2));
        $this->assertSame(1, $comparator(2, 1));
    }
}
