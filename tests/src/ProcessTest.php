<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Process;
use Kirameki\Core\Testing\TestCase;
use function sleep;
use function var_dump;

final class ProcessTest extends TestCase
{
    public function test_instantiate(): void
    {
        $process = Process::run(['ls && sleep 2'], null, ['TEST' => 'test']);
        sleep(4);
        var_dump($process->wait());
    }
}
