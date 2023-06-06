<?php

use App\Services\CommandRunner\CommandRunnerContract;
use Illuminate\Contracts\Console\Kernel;
use LaravelZero\Framework\Commands\Command;

it('can run a command', function () {
    $artisan = Mockery::mock(Kernel::class)->shouldReceive('call')->once()->with('foo')->andReturn(Command::SUCCESS)->getMock();
    $this->instance(Kernel::class, $artisan);

    expect(resolve(CommandRunnerContract::class)->run('foo'))->toBeInt()->toBe(Command::SUCCESS);
});
