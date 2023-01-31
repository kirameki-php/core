<?php declare(strict_types=1);

namespace SouthPointe\Core\Exceptions;

use ErrorException as BaseException;
use JsonSerializable;

class ErrorException extends BaseException implements Exceptionable, JsonSerializable
{
    use WithContext;

    /**
     * @param string $message
     * @param int $severity
     * @param string|null $filename
     * @param int|null $line
     */
    public function __construct(
        string $message,
        int $severity,
        ?string $filename,
        ?int $line,
    )
    {
        parent::__construct($message, 0, $severity, $filename, $line);
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
