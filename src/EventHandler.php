<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;
use Kirameki\Core\Exceptions\InvalidTypeException;

/**
 * @template TEvent of Event
 */
class EventHandler
{
    /**
     * @param class-string<TEvent> $class
     * @param list<array{ callback: Closure(TEvent): mixed, once: bool }> $listeners
     */
    public function __construct(
        protected string $class = Event::class,
        protected array $listeners = [],
    )
    {
        if (!is_a($class, Event::class, true)) {
            throw new InvalidTypeException("Expected class to be instance of " . Event::class . ", got {$class}");
        }
    }

    /**
     * @param Closure(TEvent): mixed $callback
     * @param bool $once
     * @return void
     */
    public function listen(Closure $callback, bool $once = false): void
    {
        $this->listeners[] = ['callback' => $callback, 'once' => $once];
    }

    /**
     * @param Closure(TEvent): mixed $callback
     * @return void
     */
    public function listenOnce(Closure $callback): void
    {
        $this->listen($callback, true);
    }

    /**
     * Returns the number of listeners that were removed.
     *
     * @param Closure(TEvent): mixed $callback
     * @return int<0, max>
     */
    public function removeListener(Closure $callback): int
    {
        $count = 0;
        foreach ($this->listeners as $index => $listener) {
            if ($listener['callback'] === $callback) {
                unset($this->listeners[$index]);
                $count++;
            }
        }
        if ($count > 0) {
            $this->listeners = array_values($this->listeners);
        }
        return $count;
    }

    public function removeAllListeners(): void
    {
        $this->listeners = [];
    }

    /**
     * @return bool
     */
    public function hasListeners(): bool
    {
        return $this->listeners !== [];
    }

    /**
     * @param TEvent $event
     * Event to be dispatched.
     * @param bool|null $propagationStopped
     * Flag to be set to true if the event propagation was stopped.
     * @return int<0, max>
     * The number of listeners that were called.
     */
    public function dispatch(Event $event, ?bool &$propagationStopped = null): int
    {
        if (!is_a($event, $this->class)) {
            throw new InvalidTypeException("Expected event to be instance of {$this->class}, got " . $event::class);
        }

        $evicting = [];
        $callCount = 0;
        foreach ($this->listeners as $index => $listener) {
            $listener['callback']($event);
            $callCount++;
            if ($listener['once'] || $event->willEvictCallback()) {
                $evicting[] = $index;
            }
            $event->resetAfterCall();
            if ($event->isPropagationStopped()) {
                if ($propagationStopped !== null) {
                    $propagationStopped = true;
                }
                break;
            }
        }
        if ($evicting !== []) {
            foreach ($evicting as $index) {
                unset($this->listeners[$index]);
            }
            $this->listeners = array_values($this->listeners);
        }

        return $callCount;
    }
}
