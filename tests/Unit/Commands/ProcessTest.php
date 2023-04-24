<?php

use App\Services\TransactionProcessor\Exceptions\TransactionProcessorException;
use App\Services\TransactionProcessor\TransactionProcessor;
use App\Services\TransactionReader\Exceptions\TransactionReaderException;
use App\Services\TransactionReader\TransactionReader;
use LaravelZero\Framework\Commands\Command;

beforeEach(function () {
    $this->transactionReader = Mockery::mock(TransactionReader::class);
    $this->transactionProcessor = Mockery::spy(TransactionProcessor::class);

    $this->instance(TransactionReader::class, $this->transactionReader);
    $this->instance(TransactionProcessor::class, $this->transactionProcessor);

    // The content of the file doesn't matter here, the path just needs to be valid
    $this->path = base_path('tests/stubs/transactions/valid.csv');
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

it('cannot read a spreadsheet because of a transaction reader exception', function () {
    $exception = TransactionReaderException::missingHeaders(['foo']);

    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andThrow($exception);

    $this->artisan('process', ['spreadsheet' => $this->path])
        ->expectsOutput($exception->getMessage())
        ->assertExitCode(Command::INVALID);
});

it('cannot read a spreadsheet because of a transaction processor exception', function () {
    $exception = TransactionProcessorException::cannotParseDate('foo');

    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andReturn(generator(['foo']));
    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andReturn(generator(['foo']));
    $this->transactionProcessor->shouldReceive('process')->with(['foo'])->once()->andThrow($exception);

    $this->artisan('process', ['spreadsheet' => $this->path])
        ->expectsOutput($exception->getMessage())
        ->assertExitCode(Command::INVALID);
});

it('can read a spreadsheet and pass the transactions to the transaction processor', function () {
    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andReturn(generator(['foo']));
    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andReturn(generator(['foo']));

    $this->artisan('process', ['spreadsheet' => $this->path, '--test' => true])
        ->assertExitCode(Command::SUCCESS);

    $this->transactionProcessor->shouldHaveReceived('process')->with(['foo'])->once();
});
