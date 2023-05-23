<?php declare(strict_types=1);

namespace Kirameki\Core;

class SignalEvent
{
    /**
     * @param int $signal
     * @param mixed $info
     * @param bool $terminate
     */
    public function __construct(
        public readonly int $signal,
        public readonly mixed $info,
        protected bool $terminate,
    ) {
    }

    /**
     * @param bool $toggle
     * @return void
     */
    public function shouldTerminate(bool $toggle = true): void
    {
        $this->terminate = $toggle;
    }

    /**
     * @return bool
     */
    public function markedForTermination(): bool
    {
        return $this->terminate;
    }
}
