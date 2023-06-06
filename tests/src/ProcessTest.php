<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Process;
use Kirameki\Core\Testing\TestCase;
use function dump;
use function sleep;
use function str_contains;
use const SIGINT;
use const SIGTERM;

final class ProcessTest extends TestCase
{
    public function test_instantiate(): void
    {
        {
            $process = Process::run(['sh', 'test.sh']);
            sleep(1);
            dump($process->getStatus());
            dump($process->close());
            $process = null;
        }
        sleep(1);
        dump(`ps aux`);
    }
}
