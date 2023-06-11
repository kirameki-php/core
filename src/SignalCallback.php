<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;

readonly class SignalCallback
{
    /**
     * @param Closure(SignalEvent): mixed $callback
     * @param bool $once
     */
    public function __construct(
        public Closure $callback,
        public bool $once,
    ) {
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return spl_object_id($this);
    }

    /**
     * @param SignalEvent $event
     * @return void
     */
    public function __invoke(SignalEvent $event): void
    {
        ($this->callback)($event);
    }
}
