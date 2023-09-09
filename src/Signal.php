<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\UnreachableException;
use function array_key_exists;
use function array_keys;
use function in_array;
use function pcntl_async_signals;
use function pcntl_signal;
use function pcntl_wait;
use function pcntl_wexitstatus;
use const SIG_DFL;
use const SIGABRT;
use const SIGALRM;
use const SIGBUS;
use const SIGCHLD;
use const SIGCONT;
use const SIGFPE;
use const SIGHUP;
use const SIGILL;
use const SIGINT;
use const SIGKILL;
use const SIGPIPE;
use const SIGPOLL;
use const SIGPROF;
use const SIGQUIT;
use const SIGSEGV;
use const SIGSTKFLT;
use const SIGSTOP;
use const SIGSYS;
use const SIGTERM;
use const SIGTRAP;
use const SIGTSTP;
use const SIGTTIN;
use const SIGTTOU;
use const SIGUSR1;
use const SIGUSR2;
use const SIGVTALRM;
use const SIGWINCH;
use const SIGXCPU;
use const SIGXFSZ;
use const WNOHANG;
use const WUNTRACED;

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
     * @var array<int, EventHandler<SignalEvent>>
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
        self::addCallback($signal, $callback);
    }

    /**
     * @param int $signal
     * @param Closure(SignalEvent): mixed $callback
     * @return void
     */
    protected static function addCallback(int $signal, Closure $callback): void
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

        if (!array_key_exists($signal, self::$callbacks)) {
            self::captureSignal($signal);
        }

        self::$callbacks[$signal] ??= new EventHandler(SignalEvent::class);
        self::$callbacks[$signal]->listen($callback);
    }

    /**
     * Register a callback for the given signal which will call invoke() when the signal is received.
     *
     * @param int $signal
     * Signal number to be invoked.
     * @return void
     */
    protected static function captureSignal(int $signal): void
    {
        pcntl_signal($signal, function($sig, array $info) {
            if ($sig === SIGCHLD) {
                $pid = (int) $info['pid'];
                $exitCode = $info['status'];
                while($pid > 0) {
                    self::invoke($sig, ['pid' => $pid, 'status' => $exitCode]);
                    // To understand why this is called, @see https://github.com/php/php-src/pull/11509
                    $pid = pcntl_wait($status, WUNTRACED | WNOHANG);
                    $exitCode = pcntl_wexitstatus($status);
                }
            } else {
                self::invoke($sig, $info);
            }
        });
    }

    /**
     * Invokes all callbacks for the given signal.
     * If the signal is marked for termination, this process will exit
     * with the given (signal number + 128) as specified in
     * https://tldp.org/LDP/abs/html/exitcodes.html
     *
     * @param int $signal
     * Signal number to be invoked.
     * @param mixed $sigInfo
     * Information about the signal from `pcntl_signal(...)`.
     * @return void
     */
    protected static function invoke(int $signal, mixed $sigInfo): void
    {
        if (!array_key_exists($signal, self::$callbacks)) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $event = self::createSignalEvent($signal, $sigInfo);

        self::$callbacks[$signal]->dispatch($event);

        if (!self::$callbacks[$signal]->hasListeners()) {
            unset(self::$callbacks[$signal]);
            pcntl_signal($signal, SIG_DFL);
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
     * Clear the given `$callback` for the specified signal.
     * Returns the number of callbacks removed.
     *
     * @param int $signal
     * @param Closure(SignalEvent): mixed $callback
     * @return int
     */
    public static function clearHandler(int $signal, Closure $callback): int
    {
        if (!array_key_exists($signal, self::$callbacks)) {
            return 0;
        }

        $result = self::$callbacks[$signal]->removeListener($callback);

        if (!self::$callbacks[$signal]->hasListeners()) {
            self::clearHandlers($signal);
        }

        return $result;
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
     * Get the name of the signal from the signal number.
     *
     * @param int<1, 31> $signal
     * @return string
     */
    public static function getNameOf(int $signal): string
    {
        return match ($signal) {
            SIGHUP => 'SIGHUP',
            SIGINT => 'SIGINT',
            SIGQUIT => 'SIGQUIT',
            SIGILL => 'SIGILL',
            SIGTRAP => 'SIGTRAP',
            SIGABRT => 'SIGABRT',
            SIGBUS => 'SIGBUS',
            SIGFPE => 'SIGFPE',
            SIGKILL => 'SIGKILL',
            SIGUSR1 => 'SIGUSR1',
            SIGSEGV => 'SIGSEGV',
            SIGUSR2 => 'SIGUSR2',
            SIGPIPE => 'SIGPIPE',
            SIGALRM => 'SIGALRM',
            SIGTERM => 'SIGTERM',
            SIGSTKFLT => 'SIGSTKFLT',
            SIGCHLD => 'SIGCHLD',
            SIGCONT => 'SIGCONT',
            SIGSTOP => 'SIGSTOP',
            SIGTSTP => 'SIGTSTP',
            SIGTTIN => 'SIGTTIN',
            SIGTTOU => 'SIGTTOU',
            SIGXCPU => 'SIGXCPU',
            SIGXFSZ => 'SIGXFSZ',
            SIGVTALRM => 'SIGVTALRM',
            SIGPROF => 'SIGPROF',
            SIGWINCH => 'SIGWINCH',
            SIGPOLL => 'SIGPOLL',
            SIGSYS => 'SIGSYS',
            default => throw new UnreachableException("Unknown signal: {$signal}"),
        };
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
