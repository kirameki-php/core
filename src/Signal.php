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

final class Signal
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
     * @param int $signal
     * @param Closure(SignalEvent): mixed $callback
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
     * @param int $signal
     * @param mixed $siginfo
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
     * @return array<int, int>
     */
    public static function registeredSignals(): array
    {
        return array_keys(self::$callbacks);
    }

    /**
     * @param int $signal
     * @return void
     */
    public static function clearHandlers(int $signal): void
    {
        if (array_key_exists($signal, self::$callbacks)) {
            pcntl_signal($signal, SIG_DFL);
            unset(self::$callbacks[$signal]);
        }
    }

    public static function clearAllHandlers(): void
    {
        foreach (self::registeredSignals() as $signal) {
            self::clearHandlers($signal);
        }
    }

    /**
     * @param int $signal
     * @param mixed $siginfo
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
