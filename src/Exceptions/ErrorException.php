<?php declare(strict_types=1);

namespace Kirameki\Core\Exceptions;

use ErrorException as BaseException;
use JsonSerializable;

/**
 * @consistent-constructor
 */
class ErrorException extends BaseException implements Exceptionable, JsonSerializable
{
    use WithContext;

    /**
     * @param iterable<string, mixed>|null $context
     * @param bool $clearError
     * @return static
     */
    public static function fromErrorGetLast(?iterable $context = null, bool $clearError = true): static
    {
        $error = error_get_last();

        if ($error === null) {
            throw new LogicException('No error found from error_get_last().');
        }

        if ($clearError) {
            error_clear_last();
        }

        return new static(
            $error['message'],
            $error['type'],
            $error['file'],
            $error['line'],
            $context,
        );
    }

    /**
     * @param string $message
     * @param int $severity
     * @param string|null $filename
     * @param int|null $line
     * @param iterable<string, mixed>|null $context
     */
    public function __construct(
        string $message,
        int $severity,
        ?string $filename,
        ?int $line,
        ?iterable $context = null,
    )
    {
        parent::__construct($message, 0, $severity, $filename, $line);
        $this->setContext($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'class' => $this::class,
            'message' => $this->getMessage(),
            'severity' => $this->getSeverity(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->getContext(),
        ];
    }
}
