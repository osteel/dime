<?php

use App\Services\CommandRunner\CommandRunnerContract;
use App\Services\DatabaseManager\DatabaseManagerContract;
use App\Services\DatabaseManager\Exceptions\DatabaseManagerException;
use App\Services\TransactionProcessor\Exceptions\TransactionProcessorException;
use App\Services\TransactionProcessor\TransactionProcessorContract;
use App\Services\TransactionReader\Exceptions\TransactionReaderException;
use App\Services\TransactionReader\TransactionReader;
use LaravelZero\Framework\Commands\Command;

beforeEach(function () {
    $this->databaseManager = Mockery::mock(DatabaseManagerContract::class);
    $this->transactionReader = Mockery::mock(TransactionReader::class);
    $this->transactionProcessor = Mockery::spy(TransactionProcessorContract::class);
    $this->commandRunner = Mockery::mock(CommandRunnerContract::class);

    $this->instance(DatabaseManagerContract::class, $this->databaseManager);
    $this->instance(TransactionReader::class, $this->transactionReader);
    $this->instance(TransactionProcessorContract::class, $this->transactionProcessor);
    $this->instance(CommandRunnerContract::class, $this->commandRunner);

    // The content of the file doesn't matter here, the path just needs to be valid
    $this->path = base_path('tests/stubs/transactions/valid.csv');
});

function generator(array $value): Generator
{
    yield $value;
}

it('cannot process a spreadsheet because the file is not found', function () {
    $this->artisan('process', ['spreadsheet' => 'foo'])
        ->expectsOutputToContain('No spreadsheet could be found at foo')
        ->assertExitCode(Command::INVALID);
});

it('cannot process a spreadsheet because of a database error', function () {
    $this->databaseManager->shouldReceive('prepare')->once()->andThrow(DatabaseManagerException::invalidDatabaseLocation('foo'));

    $this->artisan('process', ['spreadsheet' => $this->path])
        ->expectsOutputToContain('Configuration entry foo is invalid')
        ->assertExitCode(Command::FAILURE);
});

it('cannot process a spreadsheet because of a transaction reader exception', function () {
    $this->databaseManager->shouldReceive('prepare')->once()->andReturn();

    $exception = TransactionReaderException::missingHeaders(['foo']);

    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andThrow($exception);

    $this->artisan('process', ['spreadsheet' => $this->path])
        ->expectsOutput($exception->getMessage())
        ->assertExitCode(Command::INVALID);
});

it('cannot process a spreadsheet because of a transaction processor exception', function () {
    $this->databaseManager->shouldReceive('prepare')->once()->andReturn();

    $exception = TransactionProcessorException::cannotParseDate('foo');

    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andReturn(generator(['foo']));
    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andReturn(generator(['foo']));
    $this->transactionProcessor->shouldReceive('process')->with(['foo'])->once()->andThrow($exception);

    $this->artisan('process', ['spreadsheet' => $this->path])
        ->expectsOutput($exception->getMessage())
        ->assertExitCode(Command::INVALID);
});

it('can process a spreadsheet and pass the transactions to the transaction processor', function () {
    $this->databaseManager->shouldReceive('prepare')->once()->andReturn();
    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andReturn(generator(['foo']));
    $this->transactionReader->shouldReceive('read')->with($this->path)->once()->andReturn(generator(['foo']));
    $this->commandRunner->shouldReceive('run')->with('review')->once()->andReturn(Command::SUCCESS);

    $this->artisan('process', ['spreadsheet' => $this->path])
        ->assertExitCode(Command::SUCCESS);

    $this->transactionProcessor->shouldHaveReceived('process')->with(['foo'])->once();
});
