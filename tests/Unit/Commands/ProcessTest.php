<?php

use App\Services\TransactionProcessor\TransactionProcessor;
use App\Services\TransactionReader\TransactionReader;
use LaravelZero\Framework\Commands\Command;

beforeEach(function () {
    $this->transactionReader = Mockery::mock(TransactionReader::class);
    $this->transactionProcessor = Mockery::spy(TransactionProcessor::class);

    $this->instance(TransactionReader::class, $this->transactionReader);
    $this->instance(TransactionProcessor::class, $this->transactionProcessor);
});

it('can read a spreadsheet and pass the transactions to the transaction processor', function () {
    $path = base_path('tests/stubs/transactions.csv');

    $this->transactionReader->shouldReceive('read')->with($path)->once()->andReturn(yield []);

    $this->artisan('process', ['spreadsheet' => $path])
        ->assertExitCode(Command::SUCCESS);

    $this->transactionProcessor->shouldHaveReceived('process')->with([])->once();
});
