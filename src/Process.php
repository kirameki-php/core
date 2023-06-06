<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\RuntimeException;
use function array_keys;
use function array_map;
use function getcwd;
use function implode;
use function is_resource;
use function microtime;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function proc_terminate;
use function usleep;
use const SIGKILL;
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
     * @param int|null $timeoutSec
     * @param int $termSignal
     * @return self
     */
    public static function run(
        string|array $command,
        ?string $cwd = null,
        ?array $envs = null,
        ?int $timeoutSec = null,
        int $termSignal = SIGTERM,
    ): self
    {
        $cwd ??= (string) getcwd();
        $envVars = $envs !== null
            ? array_map(static fn($k, $v) => "{$k}={$v}", array_keys($envs), $envs)
            : null;

        $process = proc_open((array) $command, [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]], $pipes, $cwd, $envVars);
        if ($process === false) {
            throw new RuntimeException('Failed to start process.', [
                'command' => $command,
                'cwd' => $cwd,
                'envs' => $envs,
                'timeoutSec' => $timeoutSec,
                'termSignal' => $termSignal,
            ]);
        }

        $timeoutAt = microtime(true) + $timeoutSec;

        return new self($process, $command, $cwd, $envVars, $timeoutAt);
    }

    /**
     * @param resource $process
     * @param string|array<int, string> $command
     * @param string $cwd
     * @param array<int, string>|null $envs
     * @param float|null $timeoutAt
     * @param int $termSignal
     */
    protected function __construct(
        protected $process,
        public readonly string|array $command,
        public readonly string $cwd,
        public readonly ?array $envs,
        protected readonly ?float $timeoutAt = null,
        protected readonly int $termSignal = SIGTERM,
    ) {
        $this->status = proc_get_status($process);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param int $usleep
     * [Optional] Defaults to 10ms.
     * @return int
     */
    public function wait(int $usleep = 10_000): int
    {
        while ($this->isRunning()) {
            if ($this->didTimeout()) {
                $this->signal(SIGKILL);
                break;
            }
            usleep($usleep);
        }

        $this->updateStatus();

        return $this->getExitCode();
    }

    /**
     * @param int $signal
     * @return bool
     */
    public function signal(int $signal): bool
    {
        if (!is_resource($this->process)) {
            return false;
        }

        $result = proc_terminate($this->process, $signal);

        $this->updateStatus();

        return $result;
    }

    /**
     * @param float|null $timeoutSec
     * @return int
     */
    public function terminate(
        ?float $timeoutSec = null
    ): int {
        if ($this->isDone()) {
            return $this->getExitCode();
        }

        $this->signal($this->termSignal);

        if ($timeoutSec !== null) {
            usleep((int) ($timeoutSec / 1e-6));
            if ($this->isRunning()) {
                $this->signal(SIGKILL);
            }
        }

        return $this->getExitCode();
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return implode(' ', (array) $this->command);
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
        return $this->updateStatus()['running'];
    }

    /**
     * @return bool
     */
    public function isDone(): bool
    {
        return !$this->isRunning();
    }

    /**
     * @return bool
     */
    public function isStopped(): bool
    {
        return $this->updateStatus()['stopped'];
    }

    /**
     * @return bool
     */
    public function didTimeout(): bool
    {
        return $this->timeoutAt !== null
            && $this->timeoutAt > microtime(true);
    }

    /**
     * @return array{
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
    protected function updateStatus(): array
    {
        if (!is_resource($this->process)) {
            return $this->status;
        }

        $this->status = proc_get_status($this->process);

        if ($this->exitCode === null && $this->status['exitcode'] >= 0) {
            $this->exitCode = $this->status['exitcode'];
        }

        if (!$this->status['running']) {
            proc_close($this->process);
        }

        return $this->status;
    }

    /**
     * @return int
     */
    public function close(): int
    {
        $this->signal(SIGKILL);

        while ($this->isRunning()) {
            usleep(100);
        }

        return $this->getExitCode();
    }

    protected function getExitCode(): int
    {
        return $this->exitCode ?? -1;
    }

    /**
     * @return array{
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
    public function getStatus(): array
    {
        return $this->updateStatus();
    }
}
