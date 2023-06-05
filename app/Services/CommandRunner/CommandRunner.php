<?php

namespace App\Services\CommandRunner;

use Illuminate\Contracts\Console\Kernel;

final class CommandRunner implements CommandRunnerContract
{
    public function __construct(private Kernel $artisan)
    {
    }

    /** Run a command. */
    public function run(string $command): int
    {
        return $this->artisan->call($command);
    }
}
