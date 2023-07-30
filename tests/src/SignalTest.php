<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Signal;
use Kirameki\Core\SignalEvent;
use Kirameki\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\Before;
use function dump;
use function getmypid;
use function posix_kill;
use const SIGINT;
use const SIGKILL;
use const SIGSEGV;
use const SIGUSR1;

final class SignalTest extends TestCase
{
    #[Before]
    public function clearHandlers(): void
    {
        Signal::clearAllHandlers();
    }

    public function test_instantiate(): void
    {
        $this->expectExceptionMessage('Cannot instantiate static class: Kirameki\Core\Signal');
        $this->expectException(NotSupportedException::class);
        new Signal();
    }

    public function test_handle_signal(): void
    {
        $event = null;
        Signal::handle(SIGUSR1, static function(SignalEvent $e) use (&$event) {
            $event = $e;
        });

        posix_kill((int) getmypid(), SIGUSR1);

        $this->assertInstanceOf(SignalEvent::class, $event);
        $this->assertSame(SIGUSR1, $event->signal);
        $this->assertFalse($event->markedForTermination());
        $this->assertSame(getmypid(), $event->info['pid']);
        $this->assertSame([SIGUSR1], Signal::registeredSignals());
    }

    public function test_handle_signal_with_term_signals(): void
    {
        foreach (Signal::TermSignals as $signal) {
            $event = null;
            $terminates = false;
            Signal::handle($signal, static function(SignalEvent $e) use (&$event, &$terminates) {
                $event = $e;
                $terminates = $e->markedForTermination();
                $e->shouldTerminate(false);
            });

            posix_kill((int) getmypid(), $signal);

            $this->assertInstanceOf(SignalEvent::class, $event);
            $this->assertSame($signal, $event->signal);
            $this->assertTrue($terminates);
        }
        $this->assertSame(Signal::TermSignals, Signal::registeredSignals());
    }

    public function test_handle_with_kill_signal(): void
    {
        $this->expectExceptionMessage('SIGKILL and SIGSEGV cannot be captured.');
        $this->expectException(LogicException::class);

        Signal::handle(SIGKILL, static fn() => null);
    }

    public function test_handle_with_segfault_signal(): void
    {
        $this->expectExceptionMessage('SIGKILL and SIGSEGV cannot be captured.');
        $this->expectException(LogicException::class);

        Signal::handle(SIGSEGV, static fn() => null);
    }

    public function test_handle_with_eviction(): void
    {
        $count = 0;
        $e1 = null;
        Signal::handle(SIGUSR1, static function(SignalEvent $e) use (&$count, &$e1) {
            $e1 = clone $e->evictHandler();
            $count++;
        });

        $e2 = null;
        Signal::handle(SIGUSR1, static function(SignalEvent $e) use (&$count, &$e2) {
            $e2 = clone $e->evictHandler();
            $count++;
        });

        $this->assertSame([SIGUSR1], Signal::registeredSignals());

        posix_kill((int) getmypid(), SIGUSR1);

        $this->assertTrue($e1?->willEvictHandler());
        $this->assertTrue($e2?->willEvictHandler());
        $this->assertSame(2, $count);
        $this->assertSame([], Signal::registeredSignals());
    }

    public function test_handle_with_partial_eviction(): void
    {
        $count = 0;
        $e1 = null;
        Signal::handle(SIGUSR1, static function(SignalEvent $e) use (&$count, &$e1) {
            $e1 = clone $e->evictHandler();
            $count++;
        });

        $e2 = null;
        Signal::handle(SIGUSR1, static function(SignalEvent $e) use (&$count, &$e2) {
            $e2 = clone $e;
            $count++;
        });

        posix_kill((int) getmypid(), SIGUSR1);

        $this->assertTrue($e1?->willEvictHandler());
        $this->assertFalse($e2?->willEvictHandler());
        $this->assertSame([SIGUSR1], Signal::registeredSignals());

        posix_kill((int) getmypid(), SIGUSR1);

        $this->assertSame([SIGUSR1], Signal::registeredSignals());
    }

    public function test_handleOnce_signal(): void
    {
        $event = null;
        $count = 0;
        Signal::handleOnce(SIGUSR1, static function(SignalEvent $e) use (&$event, &$count) {
            $event = $e;
            $count++;
        });
        $this->assertSame([SIGUSR1], Signal::registeredSignals());

        posix_kill((int) getmypid(), SIGUSR1);

        $this->assertInstanceOf(SignalEvent::class, $event);
        $this->assertSame(SIGUSR1, $event->signal);
        $this->assertFalse($event->markedForTermination());
        $this->assertSame(getmypid(), $event->info['pid']);
        $this->assertSame(1, $count);
        $this->assertSame([], Signal::registeredSignals());
    }

    public function test_registeredSignals(): void
    {
        $this->assertSame([], Signal::registeredSignals());
        Signal::handle(SIGINT, static fn() => null);
        $this->assertSame([SIGINT], Signal::registeredSignals());
    }

    public function test_clearHandler(): void
    {
        $callback = static fn() => null;
        Signal::handle(SIGINT, $callback);
        Signal::handle(SIGUSR1, $callback);
        $this->assertSame([SIGINT, SIGUSR1], Signal::registeredSignals());
        $this->assertTrue(Signal::clearHandler(SIGUSR1, $callback));
        $this->assertFalse(Signal::clearHandler(SIGUSR1, $callback));
        $this->assertFalse(Signal::clearHandler(SIGINT, static fn() => null));
        $this->assertSame([SIGINT], Signal::registeredSignals());
    }

    public function test_clearHandlers(): void
    {
        $callback = static fn() => null;
        Signal::handle(SIGINT, $callback);
        Signal::handle(SIGUSR1, $callback);
        $this->assertSame([SIGINT, SIGUSR1], Signal::registeredSignals());
        $this->assertTrue(Signal::clearHandlers(SIGUSR1));
        $this->assertSame([SIGINT], Signal::registeredSignals());
    }

    public function test_clearHandlers_non_existing_signal(): void
    {
        $this->assertFalse(Signal::clearHandlers(SIGINT));
        $this->assertSame([], Signal::registeredSignals());
    }
}
