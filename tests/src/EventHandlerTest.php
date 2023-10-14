<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Event;
use Kirameki\Core\EventHandler;
use Kirameki\Core\Exceptions\InvalidTypeException;
use Kirameki\Core\Testing\TestCase;
use stdClass;
use Tests\Kirameki\Core\_EventHandlerTest\EventA;
use Tests\Kirameki\Core\_EventHandlerTest\EventB;

final class EventHandlerTest extends TestCase
{
    public function test_instantiate(): void
    {
        $handler = new EventHandler();
        $this->assertSame(Event::class, $handler->class);
        $this->assertFalse($handler->hasListeners());
    }

    public function test_instantiate_with_class(): void
    {
        $class = new class extends Event {};
        $handler = new EventHandler($class::class);

        $this->assertSame($class::class, $handler->class);
        $this->assertFalse($handler->hasListeners());
    }

    public function test_instantiate_wrong_class(): void
    {
        $this->expectExceptionMessage('Expected class to be instance of Kirameki\Core\Event, got stdClass');
        $this->expectException(InvalidTypeException::class);
        new EventHandler(stdClass::class);
    }

    public function test_on(): void
    {
        $handler = new EventHandler(Event::class);

        $handler->on(fn() => 1);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_emit(): void
    {
        $event = new class extends Event {};
        $handler = new EventHandler($event::class);

        $emitted = 0;
        $callback = function($e) use ($event, &$emitted) {
            $emitted++;
            $this->assertSame($event, $e);
        };

        $handler->on($callback);
        $handler->on($callback);
        $count = $handler->emit($event);

        $this->assertSame(2, $emitted);
        $this->assertSame(2, $count);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_emit_child_class(): void
    {
        $event = new class extends Event {};
        $handler = new EventHandler(Event::class);

        $dispatched = 0;
        $handler->on(function($e) use ($event, &$dispatched) {
            $dispatched++;
            $this->assertSame($event, $e);
        });
        $count = $handler->emit($event);

        $this->assertSame(1, $dispatched);
        $this->assertSame(1, $count);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_emit_and_evict(): void
    {
        $event = new class extends Event {};
        $handler = new EventHandler(Event::class);

        $dispatched = 0;
        $handler->on(function(Event $e) use (&$dispatched) {
            $e->evictCallback();
            $dispatched++;
        });

        $this->assertTrue($handler->hasListeners());
        $this->assertSame(1, $handler->emit($event));
        $this->assertSame(0, $handler->emit($event));
        $this->assertFalse($handler->hasListeners());
        $this->assertSame(1, $dispatched);
    }

    public function test_emit_and_cancel(): void
    {
        $event = new class extends Event {};
        $handler = new EventHandler(Event::class);

        $dispatched = 0;
        $handler->on(function(Event $e) use (&$dispatched) {
            $e->cancel();
            $this->assertTrue($e->isCanceled());
            $dispatched++;
        });
        $handler->on(function(Event $e) use (&$dispatched) {
            $dispatched++;
        });

        $this->assertSame(1, $handler->emit($event, $canceled));
        $this->assertFalse($event->isCanceled());
        $this->assertSame(1, $dispatched);
        $this->assertTrue($canceled);
        $this->assertSame(1, $handler->emit($event));
        $this->assertSame(2, $dispatched);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_emit_invalid_class(): void
    {
        $this->expectExceptionMessage('Expected event to be instance of ' . EventA::class . ', got ' . EventB::class);
        $this->expectException(InvalidTypeException::class);
        $event1 = new EventA();
        $event2 = new EventB();
        $handler = new EventHandler($event1::class);
        $handler->emit($event2);
    }

    public function test_off(): void
    {
        $handler = new EventHandler(Event::class);
        $callback1 = fn() => 1;
        $callback2 = fn() => 1;

        $handler->on($callback1);
        $handler->on($callback2);
        $handler->on($callback1);

        $this->assertTrue($handler->hasListeners());
        $this->assertSame(2, $handler->off($callback1));
        $this->assertSame(1, $handler->off($callback2));
        $this->assertFalse($handler->hasListeners());
    }

    public function test_removeAllListeners(): void
    {
        $handler = new EventHandler(Event::class);
        $handler->on(fn() => 1);
        $handler->on(fn() => 1);

        $this->assertTrue($handler->hasListeners());
        $this->assertSame(2, $handler->removeAllListeners());
        $this->assertFalse($handler->hasListeners());
    }

    public function test_hasListener(): void
    {
        $handler = new EventHandler();
        $this->assertSame(Event::class, $handler->class);
        $this->assertFalse($handler->hasListeners());
        $handler->on(fn() => 1);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_hasNoListener(): void
    {
        $handler = new EventHandler();
        $this->assertSame(Event::class, $handler->class);
        $this->assertTrue($handler->hasNoListeners());

        $handler->on(fn() => 1);
        $this->assertFalse($handler->hasNoListeners());
    }
}
