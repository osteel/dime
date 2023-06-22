<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

/** @codeCoverageIgnore */
class Delete extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'delete';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Delete Dime';

    /** Execute the console command. */
    public function handle(): int
    {
        return self::SUCCESS;
    }
}
