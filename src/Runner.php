<?php declare(strict_types=1);

namespace Fedot\ProcessRunner;

use function Amp\call;
use Amp\Process\Process;
use function Amp\Promise\wait;

class Runner
{
    /**
     * @var Process
     */
    private $process;

    /**
     * @var string
     */
    private $expectedText;

    /**
     * @var int
     */
    private $waitTimeout;

    public function __construct($command, string $expectedText, int $waitTimeout = 10, string $cwd = null, array $env = [], array $options = [])
    {
        $this->process = new Process($command, $cwd, $env, $options);
        $this->expectedText = $expectedText;
        $this->waitTimeout = $waitTimeout;
    }

    public function startAndWait(): void
    {
        $this->process->start();
        $this->wait();
    }

    private function wait(): void
    {
        wait(call(function (Process $process) {
            $startTime = time();
            $process->start();

            $stream = $process->getStdout();
            $output = '';

            while ($chunk = yield $stream->read()) {
                $output .= $chunk;

                if (
                    stristr($output, $this->expectedText)
                    || time() - $startTime > $this->waitTimeout
                ) {
                    break;
                }
            }
        }, $this->process));
    }
}
