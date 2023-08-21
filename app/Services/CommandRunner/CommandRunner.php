<?php

namespace App\Services\CommandRunner;

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CommandRunner implements CommandRunnerContract
{
    public function __construct(private Kernel $artisan)
    {
    }

    /**
     * Run a command.
     *
     * @param array<mixed,mixed> $parameters
     */
    public function run(string $command, array $parameters = [], ?OutputInterface $output = null): int
    {
        return $this->artisan->call($command, $parameters, $output);
    }
}
