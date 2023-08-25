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
     * @param list<Closure(TEvent): mixed> $listeners
     */
    public function __construct(
        public string $class = Event::class,
        protected array $listeners = [],
    )
    {
        if (!is_a($class, Event::class, true)) {
            throw new InvalidTypeException("Expected class to be instance of " . Event::class . ", got {$class}");
        }
    }

    /**
     * @param Closure(TEvent): mixed $callback
     * @return void
     */
    public function listen(Closure $callback): void
    {
        $this->listeners[] = $callback;
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
            if ($listener === $callback) {
                unset($this->listeners[$index]);
                $count++;
            }
        }
        if ($count > 0) {
            $this->listeners = array_values($this->listeners);
        }
        return $count;
    }

    /**
     * Returns the number of listeners that were removed.
     *
     * @return int<0, max>
     */
    public function removeAllListeners(): int
    {
        $count = count($this->listeners);
        $this->listeners = [];
        return $count;
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
     * @param bool|null $wasCanceled
     * Flag to be set to true if the event propagation was stopped.
     * @return int<0, max>
     * The number of listeners that were called.
     */
    public function dispatch(Event $event, ?bool &$wasCanceled = false): int
    {
        if (!is_a($event, $this->class)) {
            throw new InvalidTypeException("Expected event to be instance of {$this->class}, got " . $event::class);
        }

        $evicting = [];
        $callCount = 0;
        foreach ($this->listeners as $index => $listener) {
            $listener($event);
            $callCount++;
            if ($event->willEvictCallback()) {
                $evicting[] = $index;
            }
            $event->resetAfterCall();
            if ($event->isCanceled()) {
                $wasCanceled = true;
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
