<?php

namespace App\Commands;

use App\Services\CommandRunner\CommandRunnerContract;
use App\Services\DatabaseManager\DatabaseManagerContract;
use App\Services\DatabaseManager\Exceptions\DatabaseManagerException;
use App\Services\TransactionProcessor\Exceptions\TransactionProcessorException;
use App\Services\TransactionProcessor\TransactionProcessorContract;
use App\Services\TransactionReader\Exceptions\TransactionReaderException;
use App\Services\TransactionReader\TransactionReader;

final class Process extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'process {spreadsheet : Absolute or relative path to the spreadsheet to process}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Process a spreadsheet of transactions';

    /** Execute the console command. */
    public function handle(
        DatabaseManagerContract $database,
        TransactionReader $transactionReader,
        TransactionProcessorContract $transactionProcessor,
        CommandRunnerContract $commandRunner,
    ): int {
        $spreadsheet = $this->argument('spreadsheet');

        assert(is_string($spreadsheet));

        if (! is_file($spreadsheet)) {
            $this->error(sprintf('No spreadsheet could be found at %s', $spreadsheet));

            return self::INVALID;
        }

        try {
            $database->prepare();
        } catch (DatabaseManagerException $exception) {
            $this->error(sprintf('Database error: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $this->info(sprintf('Processing %s...', basename($spreadsheet)));

        try {
            $this->progressStart(iterator_count($transactionReader->read($spreadsheet)));
        } catch (TransactionReaderException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        foreach ($transactionReader->read($spreadsheet) as $transaction) {
            try {
                $transactionProcessor->process($transaction);
            } catch (TransactionProcessorException $exception) {
                $this->progressComplete();
                $this->error($exception->getMessage());

                return self::INVALID;
            }

            $this->progressAdvance();
        }

        $this->progressComplete();

        $this->success('Transactions successfully processed!');

        return $commandRunner->run(command: 'review', output: $this->output);
    }
}
