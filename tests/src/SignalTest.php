<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Signal;
use Kirameki\Core\SignalEvent;
use Tests\Kirameki\Core\Exceptions\TestCase;
use PHPUnit\Framework\Attributes\Before;
use function getmypid;
use function posix_kill;
use const SIGINT;
use const SIGKILL;
use const SIGUSR1;

final class SignalTest extends TestCase
{
    #[Before]
    public function clearHandlers(): void
    {
        Signal::clearAllHandlers();
    }

    public function test_handle(): void
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

    public function test_handle_with_term_signals(): void
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
        $this->expectExceptionMessage('SIGKILL cannot be captured.');
        $this->expectException(LogicException::class);

        Signal::handle(SIGKILL, static fn() => null);
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

        Signal::clearHandlers(SIGUSR1);
        $this->assertSame([SIGINT], Signal::registeredSignals());
    }

    public function test_clearHandler_with_callback(): void
    {
        $callback = static fn() => 0;
        $callbackAlt = static fn() => 1;
        Signal::handle(SIGINT, $callback);
        Signal::handle(SIGUSR1, $callback);
        Signal::handle(SIGUSR1, $callbackAlt);
        $this->assertSame([SIGINT, SIGUSR1], Signal::registeredSignals());

        Signal::clearHandlers(SIGUSR1, $callback);
        $this->assertSame([SIGINT, SIGUSR1], Signal::registeredSignals());
        Signal::clearHandlers(SIGUSR1, $callbackAlt);
        $this->assertSame([SIGINT], Signal::registeredSignals());
    }
}
