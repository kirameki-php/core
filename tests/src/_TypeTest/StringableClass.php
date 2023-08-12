<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\_TypeTest;

use Stringable;

class StringableClass implements Stringable
{
    public function __toString(): string
    {
        return 'stringable';
    }
}
