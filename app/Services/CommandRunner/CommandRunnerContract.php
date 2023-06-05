<?php

namespace App\Services\CommandRunner;

interface CommandRunnerContract
{
    /** Run a command. */
    public function run(string $command): int;
}
