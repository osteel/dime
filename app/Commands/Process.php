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
    protected $signature = 'process {spreadsheet : Absolute or relative path to the spreadsheet to process}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Process a spreadsheet of transactions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(TransactionReader $transactionReader, TransactionProcessor $transactionProcessor)
    {
        if (! is_file($spreadheet = $this->argument('spreadsheet'))) {
            $this->error(sprintf('No spreadsheet could be found at %s', $spreadheet));

            return self::INVALID;
        }

        //$transactions = $transactionReader->read($spreadheet);
        //$this->withProgressBar($transactions, fn (array $transaction) => $transactionProcessor->process($transaction);

        $bar = $this->output->createProgressBar(iterator_count($transactionReader->read($spreadheet)));

        $bar->start();

        foreach ($transactionReader->read($spreadheet) as $transaction) {
            print_r($transaction);
            $transactionProcessor->process($transaction);

            $bar->advance();
        }

        $bar->finish();

        return self::SUCCESS;
    }
}
