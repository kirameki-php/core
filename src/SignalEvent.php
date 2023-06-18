<?php declare(strict_types=1);

namespace Kirameki\Core;

class SignalEvent
{
    /**
     * @param int $signal
     * @param mixed $info
     * @param bool $terminate
     * @param bool $evictHandler
     */
    public function __construct(
        public readonly int $signal,
        public readonly mixed $info,
        protected bool $terminate,
        protected bool $evictHandler = false,
    ) {
    }

    /**
     * Mark signal for termination.
     * When this is set to **true**, the application will exit after
     * all the signal callbacks have been processed.
     *
     * @param bool $toggle
     * [Optional] Toggles termination.
     * Defaults to **true**.
     * @return $this
     */
    public function shouldTerminate(bool $toggle = true): static
    {
        $this->terminate = $toggle;
        return $this;
    }

    /**
     * Returns whether the signal is marked for termination.
     *
     * @return bool
     */
    public function markedForTermination(): bool
    {
        return $this->terminate;
    }

    /**
     * Mark signal callback for removal.
     * When this is set to **true**, the signal callback will be removed.
     *
     * @return $this
     */
    public function evictHandler(bool $toggle = true): static
    {
        $this->evictHandler = $toggle;
        return $this;
    }

    /**
     * Returns whether the signal callback should be removed.
     *
     * @return bool
     */
    public function willEvictHandler(): bool
    {
        return $this->evictHandler;
    }
}
