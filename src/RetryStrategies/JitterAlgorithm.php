<?php declare(strict_types=1);

namespace Kirameki\Core\RetryStrategies;

enum JitterAlgorithm
{
    case None;
    case Full;
    case Equal;
    case Decorrelated;
}
