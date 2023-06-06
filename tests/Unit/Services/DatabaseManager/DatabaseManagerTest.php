<?php

use App\Services\DatabaseManager\DatabaseManagerContract;
use App\Services\DatabaseManager\Exceptions\DatabaseManagerException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\PendingProcess;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\NullOutput;

beforeEach(function () {
    $this->instance(Repository::class, $this->config = Mockery::mock(Repository::class));
    $this->instance(PendingProcess::class, $this->process = Mockery::mock(PendingProcess::class));
    $this->instance(Kernel::class, $this->artisan = Mockery::mock(Kernel::class));

    $this->databaseManager = resolve(DatabaseManagerContract::class);
    $this->entry = 'database.connections.testing.database';

    $this->config->shouldReceive('get')->once()->with('app.env')->andReturn('testing');
});

it('cannot prepare the database because the database location is invalid', function () {
    $this->config->shouldReceive('get')->once()->with($this->entry)->andReturn([]);
    $this->process->shouldNotReceive('run');
    $this->artisan->shouldNotReceive('call');

    expect(fn () => $this->databaseManager->prepare())->toThrow(
        DatabaseManagerException::class,
        DatabaseManagerException::invalidDatabaseLocation($this->entry)->getMessage(),
    );
});

it('cannot prepare the database because of a process error', function () {
    $this->config->shouldReceive('get')->once()->with($this->entry)->andReturn('foo/bar');

    $exception = new ProcessFailedException(Mockery::spy(ProcessResult::class));

    $this->process->shouldReceive('run')->once()->with('mkdir -m755 foo')->andThrow($exception);
    $this->artisan->shouldNotReceive('call');

    expect(fn () => $this->databaseManager->prepare())
        ->toThrow(DatabaseManagerException::class, DatabaseManagerException::processError($exception)->getMessage());
});

it('can create the database file', function () {
    $path = base_path('tests/stubs/transactions/foo');

    $this->config->shouldReceive('get')->once()->with($this->entry)->andReturn($path);
    $this->process->shouldReceive('run')->once()->with(sprintf('touch %s', $path))->andReturn(Mockery::spy(ProcessResult::class));
    $this->artisan->shouldReceive('call')->once()->andReturn(Command::SUCCESS);

    $this->databaseManager->prepare();
});

it('can prepare the database', function () {
    $this->config->shouldReceive('get')->once()->with($this->entry)->andReturn(base_path('tests/stubs/transactions/valid.csv'));
    $this->process->shouldNotReceive('run');

    $this->artisan->shouldReceive('call')
        ->once()
        ->withArgs(fn (string $command, array $parameters, NullOutput $output) => $command === 'migrate:fresh' && $parameters === ['--force' => true])
        ->andReturn(Command::SUCCESS);

    $this->databaseManager->prepare();
});
