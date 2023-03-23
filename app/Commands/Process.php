<?php

namespace App\Commands;

use App\Services\TransactionProcessor\TransactionProcessor;
use App\Services\TransactionReader\TransactionReader;
use LaravelZero\Framework\Commands\Command;

class Process extends Command
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
    public function handle(TransactionReader $transactionReader, TransactionProcessor $transactionProcessor): int
    {
        $spreadsheet = $this->argument('spreadsheet');

        assert(is_string($spreadsheet));

        if (! is_file($spreadsheet)) {
            $this->error(sprintf('No spreadsheet could be found at %s', $spreadsheet));

            return self::INVALID;
        }

        if (! $this->option('test')) {
            $this->callSilent('migrate:fresh');
        }

        $bar = $this->output->createProgressBar(iterator_count($transactionReader->read($spreadsheet)));

        $bar->start();

        foreach ($transactionReader->read($spreadsheet) as $transaction) {
            $transactionProcessor->process($transaction);

            $bar->advance();
        }

        $bar->finish();

        return self::SUCCESS;
    }
}
