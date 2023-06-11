<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use function array_key_exists;
use function array_keys;
use function in_array;
use function pcntl_async_signals;
use function pcntl_signal;
use function spl_object_id;
use const SIG_DFL;
use const SIGHUP;
use const SIGINT;
use const SIGKILL;
use const SIGQUIT;
use const SIGSEGV;
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
     * @var array<int, list<SignalCallback>>
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
        self::addCallback($signal, new SignalCallback($callback, false));
    }

    /**
     * Adds `$callback` to the signal handler that will be executed only once.
     *
     * @param int $signal
     * Signal number to handle.
     * @param Closure(SignalEvent): mixed $callback
     * Callback to be invoked when the signal is received.
     * @return void
     */
    public static function handleOnce(int $signal, Closure $callback): void
    {
        self::addCallback($signal, new SignalCallback($callback, true));
    }

    /**
     * @param int $signal
     * @param SignalCallback $callback
     * @return void
     */
    protected static function addCallback(int $signal, SignalCallback $callback): void
    {
        if ($signal === SIGKILL || $signal === SIGSEGV) {
            throw new LogicException('SIGKILL and SIGSEGV cannot be captured.', [
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

        self::$callbacks[$signal][$callback->getObjectId()] = $callback;
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
            if ($callback->once) {
                unset(self::$callbacks[$signal][$callback->getObjectId()]);
                if (self::$callbacks[$signal] === []) {
                    unset(self::$callbacks[$signal]);
                    pcntl_signal($signal, SIG_DFL);
                }
            }
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
     *
     * @param int $signal
     * Signal to clear.
     * @return bool
     */
    public static function clearHandlers(int $signal): bool
    {
        if (!array_key_exists($signal, self::$callbacks)) {
            return false;
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
