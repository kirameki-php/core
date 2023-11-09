<?php declare(strict_types=1);

namespace Kirameki\Core\Exceptions;

trait WithContext
{
    /**
     * @var array<string, mixed>|null $context
     */
    protected ?array $context = null;

    /**
     * @inheritDoc
     */
    public function getContext(): array
    {
        return $this->context ?? [];
    }

    /**
     * @param iterable<string, mixed>|null $context
     * @return $this
     */
    public function setContext(?iterable $context): static
    {
        if ($context === null) {
            $this->context = null;
            return $this;
        }

        $this->context = [];
        foreach ($context as $key => $val) {
            $this->addContext($key, $val);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $val
     * @return $this
     */
    public function addContext(string $key, mixed $val): static
    {
        $this->context ??= [];
        $this->context[$key] = $val;
        return $this;
    }

    /**
     * @param iterable<string, mixed> $context
     * @return $this
     */
    public function mergeContext(iterable $context): static
    {
        foreach ($context as $key => $val) {
            $this->addContext($key, $val);
        }
        return $this;
    }
}
