<?php declare(strict_types=1);

namespace SouthPointe\Core\Exceptions;

use Throwable;

interface Exceptionable extends Throwable
{
    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array;
}
