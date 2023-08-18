<?php

use App\Services\CommandRunner\CommandRunnerContract;
use Domain\Enums\FiatCurrency;
use LaravelZero\Framework\Commands\Command;

it('can process a spreadsheet', function () {
    $this->instance(CommandRunnerContract::class, $commandRunner = Mockery::spy(CommandRunnerContract::class));

    $path = base_path('tests/stubs/transactions/valid.csv');

    $this->artisan('process', ['spreadsheet' => $path])->assertExitCode(Command::SUCCESS);

    $this->assertDatabaseCount('tax_year_summaries', 2);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2019-2020',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '60.6060606060606060606060606060606',
            'proceeds' => '60',
            'difference' => '-0.6060606060606060606060606060606',
        ]),
        'income' => '0',
        'non_attributable_allowable_cost' => '60',
    ]);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2020-2021',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '15709.51947887970615243342516069788787',
            'proceeds' => '27979.5',
            'difference' => '12269.98052112029384756657483930211213',
        ]),
        'income' => '1000',
        'non_attributable_allowable_cost' => '117',
    ]);

    $this->assertDatabaseHas('summaries', [
        'currency' => FiatCurrency::GBP->value,
        'fiat_balance' => '3330',
    ]);

    $commandRunner->shouldHaveReceived('run')->once()->with('review');
});
