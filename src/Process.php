<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\RuntimeException;
use function array_keys;
use function array_map;
use function implode;
use function microtime;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function usleep;
use const SIGTERM;

class Process
{
    /**
     * @var array{
     *     command: string,
     *     pid: int,
     *     running: bool,
     *     signaled: bool,
     *     stopped: bool,
     *     exitcode: int,
     *     termsig: int,
     *     stopsig: int,
     *  }
     */
    protected array $status;

    /**
     * @var int|null
     */
    public ?int $exitCode = null;

    /**
     * @param string|array<int, string> $command
     * @param string|null $cwd
     * @param array<string, string>|null $envs
     * @return self
     */
    public static function run(
        string|array $command,
        ?string $cwd = null,
        ?array $envs = null,
        ?int $timeoutSec = null,
    ): self
    {
        $command = 'exec ' . implode(' ', (array) $command);
        $cwd ??= (string) getcwd();
        $envVars = $envs !== null
            ? array_map(static fn($k, $v) => "{$k}={$v}", array_keys($envs), $envs)
            : null;

        $process = proc_open($command, [], $pipes, $cwd, $envVars);
        if ($process === false) {
            throw new RuntimeException('Failed to start process.', [
                'command' => $command,
                'cwd' => $cwd,
            ]);
        }

        $timeoutAt = microtime(true) + $timeoutSec;

        return new self($process, $cwd, $envVars, $timeoutAt);
    }

    /**
     * @param resource $process
     * @param string $cwd
     * @param array<int, string>|null $envs
     * @param float|null $timeoutAt
     */
    protected function __construct(
        protected $process,
        public readonly string $cwd,
        public readonly ?array $envs,
        protected readonly ?float $timeoutAt = null,
    ) {
        $this->status = proc_get_status($process);
    }

    /**
     * @return int
     */
    public function wait(): int
    {
        while ($this->isRunning()) {
            if ($this->didTimeout()) {
                $this->terminate();
                break;
            }
            usleep(1000);
        }

        $this->exitCode = proc_close($this->process);
        return (int) $this->exitCode;
    }

    /**
     * @param int $signal
     * @return bool
     */
    public function terminate(int $signal = SIGTERM): bool
    {
        return proc_terminate($this->process, $signal);
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->status['command'];
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->status['pid'];
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        $this->updateStatus();
        return $this->status['running'];
    }

    /**
     * @return bool
     */
    public function didTimeout(): bool
    {
        return $this->timeoutAt !== null
            && $this->timeoutAt < microtime(true);
    }

    /**
     * @return void
     */
    protected function updateStatus(): void
    {
        $this->status = proc_get_status($this->process);
    }
}
