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

function generator(array $value): Generator
{
    yield $value;
}

it('cannot read a spreadsheet because the file is not found', function () {
    $this->artisan('process', ['spreadsheet' => 'foo'])
        ->expectsOutput('No spreadsheet could be found at foo')
        ->assertExitCode(Command::INVALID);
});

it('can read a spreadsheet and pass the transactions to the transaction processor', function () {
    $path = base_path('tests/stubs/transactions.csv');

    $this->transactionReader->shouldReceive('read')->with($path)->once()->andReturn(generator(['foo']));
    $this->transactionReader->shouldReceive('read')->with($path)->once()->andReturn(generator(['foo']));

    $this->artisan('process', ['spreadsheet' => $path, '--test' => true])
        ->assertExitCode(Command::SUCCESS);

    $this->transactionProcessor->shouldHaveReceived('process')->with(['foo'])->once();
});
