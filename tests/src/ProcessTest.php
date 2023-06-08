<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Process;
use Kirameki\Core\Testing\TestCase;
use function dump;
use function usleep;

final class ProcessTest extends TestCase
{
    public function test_instantiate(): void
    {
        {
            $process = Process::command(['sh', 'test.sh'])
                ->in(__DIR__)
                ->run();
            while ($process->isRunning()) {
                $out = $process->readStdout();
                if ($out !== '') {
                    dump($out);
                }
                usleep(10_000);
            }

            dump('done');

            usleep(1000);

            $out = $process->readStdout();
            dump($out);
        }
    }
}
