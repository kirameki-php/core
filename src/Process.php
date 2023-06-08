<?php declare(strict_types=1);

namespace Kirameki\Core;

use DateTimeInterface;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Stream\FileStream;
use function array_keys;
use function array_map;
use function getcwd;
use function microtime;
use function proc_open;
use const SIGKILL;
use const SIGTERM;

class Process
{
    /**
     * @param string|array<int, string> $command
     * @param string|null $cwd
     * @param array<string, string>|null $envs
     * @param float|null $timeoutAt
     * @param int|null $timeoutSignal
     * @param int|null $termSignal
     */
    final protected function __construct(
        protected string|array $command,
        protected ?string $cwd = null,
        protected ?array $envs = null,
        protected ?float $timeoutAt = null,
        protected ?int $timeoutSignal = null,
        protected ?int $termSignal = null,
        protected ?FileStream $stdout = null,
        protected ?FileStream $stderr = null,
    ) {
    }

    /**
     * @param string|array<int, string> $command
     * @return static
     */
    public static function command(string|array $command): static
    {
        return new static($command);
    }

    /**
     * @param non-empty-string $directory
     * @return $this
     */
    public function directory(?string $directory): static
    {
        $this->cwd = $directory;
        return $this;
    }

    /**
     * @param array<string, string> $envs
     * @return $this
     */
    public function envs(?array $envs): static
    {
        $this->envs = $envs;
        return $this;
    }

    /**
     * @param int|float $seconds
     * @return $this
     */
    public function timeoutIn(int|float|null $seconds): static
    {
        $this->timeoutAt = microtime(true) + $seconds;
        return $this;
    }

    /**
     * @param DateTimeInterface $dateTime
     * @return $this
     */
    public function timeoutAt(DateTimeInterface $dateTime): static
    {
        $this->timeoutAt = (float) $dateTime->format('U.u');
        return $this;
    }

    /**
     * @param int $signal
     * @return $this
     */
    public function termSignal(?int $signal): static
    {
        $this->termSignal = $signal;
        return $this;
    }

    /**
     * @return $this
     */
    public function noOutput(): static
    {
        $stream = new FileStream('/dev/null', 'w+');
        return $this
            ->stdout($stream)
            ->stderr($stream);
    }

    /**
     * @param FileStream|null $stream
     * @return $this
     */
    public function stdout(?FileStream $stream): static
    {
        $this->stdout = $stream;
        return $this;
    }

    /**
     * @param FileStream|null $stream
     * @return $this
     */
    public function stderr(?FileStream $stream): static
    {
        $this->stderr = $stream;
        return $this;
    }

    /**
     * @return ProcessHandler
     */
    public function run(): ProcessHandler
    {
        $envVars = $this->envs !== null
            ? array_map(static fn($k, $v) => "{$k}={$v}", array_keys($this->envs), $this->envs)
            : null;

        $this->cwd ??= (string) getcwd();
        $this->timeoutSignal ??= SIGKILL;
        $this->termSignal ??= SIGTERM;

        $descriptorSpec = [
            ["pipe", "r"], ["pipe", "w"], ["pipe", "w"],
        ];

        $process = proc_open($this->command, $descriptorSpec, $pipes, $this->cwd, $envVars);
        if ($process === false) {
            throw new RuntimeException('Failed to start process.', [
                'command' => $this->command,
                'cwd' => $this->cwd,
                'envs' => $this->envs,
                'timeoutAt' => $this->timeoutAt,
                'timeoutSignal' => $this->timeoutSignal,
                'termSignal' => $this->termSignal,
            ]);
        }

        $this->stdout ??= new FileStream('php://temp/maxmemory:'.(1024 * 1024));
        $this->stderr ??= new FileStream('php://temp/maxmemory:'.(1024 * 1024));

        return new ProcessHandler(
            $process,
            $this->command,
            $this->cwd,
            $envVars,
            $pipes,
            $this->timeoutAt,
            $this->timeoutSignal,
            $this->termSignal,
            $this->stdout,
            $this->stderr,
        );
    }
}
