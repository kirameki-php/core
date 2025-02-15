<?php declare(strict_types=1);

use Kirameki\Core\Exceptions\UnreachableException;

function unreachable(): never
{
    throw new UnreachableException();
}
