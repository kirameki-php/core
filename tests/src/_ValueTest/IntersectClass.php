<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\_ValueTest;

use Countable;
use Stringable;

class IntersectClass implements Countable, Stringable
{
    public function __toString(): string
    {
        return 'stringable';
    }

    public function count(): int
    {
        return 0;
    }
}
