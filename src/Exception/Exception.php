<?php declare(strict_types=1);

namespace Kirameki\Core\Exception;

use Exception as PhpException;
use Throwable;

abstract class Exception extends PhpException
{
    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param mixed|null $context
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        Throwable $previous = null,
        protected mixed $context = null,
    )
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param mixed $context
     */
    public function setContext(mixed $context): void
    {
        $this->context = $context;
    }
}
