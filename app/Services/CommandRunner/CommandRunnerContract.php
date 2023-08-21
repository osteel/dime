<?php

namespace App\Services\CommandRunner;

use Symfony\Component\Console\Output\OutputInterface;

interface CommandRunnerContract
{
    /**
     * Run a command.
     *
     * @param array<mixed,mixed> $parameters
     */
    public function run(string $command, array $parameters = [], ?OutputInterface $output = null): int;
}
