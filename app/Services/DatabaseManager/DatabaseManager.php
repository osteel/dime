<?php

namespace App\Services\DatabaseManager;

use App\Services\DatabaseManager\Exceptions\DatabaseManagerException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\PendingProcess;
use Symfony\Component\Console\Output\NullOutput;

final class DatabaseManager implements DatabaseManagerContract
{
    public function __construct(
        private Repository $config,
        private PendingProcess $process,
        private Kernel $artisan,
    ) {
    }

    /**
     * Prepare the database for transaction processing.
     *
     * @throws DatabaseManagerException
     */
    public function prepare(): void
    {
        $entry = sprintf('database.connections.%s.database', $this->config->get('app.env')); // @phpstan-ignore-line

        is_string($file = $this->config->get($entry)) || throw DatabaseManagerException::invalidDatabaseLocation($entry);

        try {
            is_file($file) || $this->initialise($file);
        } catch (ProcessFailedException $exception) {
            throw DatabaseManagerException::processError($exception);
        }

        $this->artisan->call('migrate:fresh', ['--force' => true], new NullOutput());
    }

    /** @throws ProcessFailedException */
    private function initialise(string $file): void
    {
        if ($file === ':memory:') {
            return;
        }

        is_dir($directory = dirname($file)) || $this->process->run(sprintf('mkdir -m755 %s', $directory))->throw();

        $this->process->run(sprintf('touch %s', $file))->throw();
    }
}
