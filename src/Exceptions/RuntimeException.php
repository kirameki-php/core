<?php declare(strict_types=1);

namespace Kirameki\Core\Exceptions;

use JsonSerializable;
use RuntimeException as BaseException;
use Throwable;

class RuntimeException extends BaseException implements Exceptionable, JsonSerializable
{
    use WithContext;
    use WithJsonSerialize;

    /**
     * @param string $message
     * @param iterable<string, mixed>|null $context
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = "",
        ?iterable $context = null,
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, $code, $previous);
        $this->setContext($context);
    }
}
