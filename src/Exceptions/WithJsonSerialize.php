<?php declare(strict_types=1);

namespace Kirameki\Core\Exceptions;

use Throwable;

trait WithJsonSerialize
{
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toJson($this);
    }

    /**
     * @param Throwable $throwable
     * @return array<string, mixed>
     */
    protected function toJson(Throwable $throwable): array
    {
        $data = [];
        $data['class'] = $throwable::class;
        $data['message'] = $throwable->getMessage();
        $data['code'] = $throwable->getCode();
        $data['file'] = $throwable->getFile();
        $data['line'] = $throwable->getLine();
        $data['trace'] = $throwable->getTrace();

        if ($throwable instanceof Exceptionable) {
            $data['context'] = $this->getContext();
        }

        if ($throwable->getPrevious() !== null) {
            $data['previous'] = $this->toJson($throwable->getPrevious());
        }

        return $data;
    }
}
