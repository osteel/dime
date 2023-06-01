<?php

namespace App\Commands;

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
    protected $signature = 'process
        {spreadsheet : Absolute or relative path to the spreadsheet to process}
        {--test : Run the command in test mode}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Process a spreadsheet of transactions';

    /** Execute the console command. */
    public function handle(TransactionReader $transactionReader, TransactionProcessorContract $transactionProcessor): int
    {
        $spreadsheet = $this->argument('spreadsheet');

        assert(is_string($spreadsheet));

        if (! is_file($spreadsheet)) {
            $this->presenter->error(sprintf('No spreadsheet could be found at %s', $spreadsheet));

            return self::INVALID;
        }

        if (! $this->option('test')) {
            $this->callSilent('migrate:fresh');
        }

        $this->presenter->info(sprintf('Processing %s...', basename($spreadsheet)));

        try {
            $this->presenter->progressStart(iterator_count($transactionReader->read($spreadsheet)));
        } catch (TransactionReaderException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        foreach ($transactionReader->read($spreadsheet) as $transaction) {
            try {
                $transactionProcessor->process($transaction);
            } catch (TransactionProcessorException $exception) {
                $this->presenter->progressComplete();
                $this->error($exception->getMessage());

                return self::INVALID;
            }

            $this->presenter->progressAdvance();
        }

        $this->presenter->progressComplete();

        $this->presenter->success('Transactions successfully processed!');

        return $this->option('test') ? self::SUCCESS : $this->call('review');
    }
}
