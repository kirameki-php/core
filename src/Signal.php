<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use function array_key_exists;
use function array_keys;
use function in_array;
use function pcntl_async_signals;
use function pcntl_signal;
use const SIG_DFL;
use const SIGHUP;
use const SIGINT;
use const SIGKILL;
use const SIGQUIT;
use const SIGTERM;

final class Signal extends StaticClass
{
    /**
     * @see https://www.gnu.org/software/libc/manual/html_node/Termination-Signals.html
     */
    public final const TermSignals = [
        SIGHUP,  // 1
        SIGINT,  // 2
        SIGQUIT, // 3
        SIGTERM, // 15
    ];

    /**
     * @var array<int, list<Closure(SignalEvent): mixed>>
     */
    private static array $callbacks = [];

    /**
     * Adds `$callback` to the signal handler.
     *
     * @param int $signal
     * Signal number to handle.
     * @param Closure(SignalEvent): mixed $callback
     * Callback to be invoked when the signal is received.
     * @return void
     */
    public static function handle(int $signal, Closure $callback): void
    {
        if ($signal === SIGKILL) {
            throw new LogicException('SIGKILL cannot be captured.', [
                'signal' => $signal,
                'callback' => $callback,
            ]);
        }

        // Set async on once.
        if (self::$callbacks === []) {
            pcntl_async_signals(true);
        }

        // Set signal handler trigger once.
        if (!array_key_exists($signal, self::$callbacks)) {
            pcntl_signal($signal, self::invoke(...));
        }

        self::$callbacks[$signal][] = $callback;
    }

    /**
     * Invokes all callbacks for the given signal.
     * If the signal is marked for termination, this process will exit
     * with the given (signal number + 128) as specified in
     * https://tldp.org/LDP/abs/html/exitcodes.html
     *
     * @param int $signal
     * Signal number to be invoked.
     * @param mixed $siginfo
     * Information about the signal from `pcntl_signal(...)`.
     * @return void
     */
    protected static function invoke(int $signal, mixed $siginfo): void
    {
        $event = self::createSignalEvent($signal, $siginfo);

        foreach (self::$callbacks[$signal] as $callback) {
            $callback($event);
        }

        if ($event->markedForTermination()) {
            /** @see https://tldp.org/LDP/abs/html/exitcodes.html **/
            // @codeCoverageIgnoreStart
            exit(128 + $signal);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Returns all the registered signals.
     *
     * @return array<int, int>
     */
    public static function registeredSignals(): array
    {
        return array_keys(self::$callbacks);
    }

    /**
     * Clears the signal handlers for the specified signal.
     * If `$callback` is specified, that specific handler will be cleared.
     * When there are no more handlers for the signal, the signal will be
     * restored to its default behavior using `SIG_DFL`.
     *
     * @param int $signal
     * Signal to clear.
     * @param Closure(SignalEvent): mixed|null $callback
     * [Optional] Specific handler to clear.
     * Defaults to **null**.
     * @return bool
     */
    public static function clearHandlers(int $signal, ?Closure $callback = null): bool
    {
        if (!array_key_exists($signal, self::$callbacks)) {
            return false;
        }

        // Clear specific handler.
        if ($callback !== null) {
            $cleared = false;
            foreach (self::$callbacks[$signal] as $index => $each) {
                if ($each === $callback) {
                    unset(self::$callbacks[$signal][$index]);
                    $cleared = true;
                    break;
                }
            }
            if (self::$callbacks[$signal] === []) {
                unset(self::$callbacks[$signal]);
                pcntl_signal($signal, SIG_DFL);
            }
            return $cleared;
        }

        // Clear all handlers.
        unset(self::$callbacks[$signal]);
        pcntl_signal($signal, SIG_DFL);
        return true;
    }

    /**
     * Clears all the signal handlers.
     *
     * @return void
     */
    public static function clearAllHandlers(): void
    {
        foreach (self::registeredSignals() as $signal) {
            self::clearHandlers($signal);
        }
    }

    /**
     * Creates a new signal event.
     * Event will be marked for termination if the signal is a termination signal.
     *
     * @param int $signal
     * Signal number to be set.
     * @param mixed $siginfo
     * Signal information from `pcntl_signal(...)` to be set.
     * @return SignalEvent
     */
    protected static function createSignalEvent(int $signal, mixed $siginfo): SignalEvent
    {
        return new SignalEvent(
            $signal,
            $siginfo,
            in_array($signal, self::TermSignals, true),
        );
    }
}
