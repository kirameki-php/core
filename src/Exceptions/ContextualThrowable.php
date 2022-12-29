<?php declare(strict_types=1);

namespace SouthPointe\Core\Exceptions;

interface ContextualThrowable
{
    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array;
}
