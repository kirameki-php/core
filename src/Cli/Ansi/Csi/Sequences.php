<?php declare(strict_types=1);

namespace Kirameki\Core\Cli\Ansi\Csi;

use Kirameki\Core\Cli\Ansi\Csi;
use Stringable;

abstract class Sequences implements Stringable
{
    protected function __construct(
        private readonly string $value,
        private readonly Csi $code,
    )
    {
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value . $this->code->value;
    }
}
