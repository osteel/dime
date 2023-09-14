<?php

use App\Services\CommandRunner\CommandRunnerContract;
use Domain\Enums\FiatCurrency;

it('can process a spreadsheet', function () {
    $this->instance(CommandRunnerContract::class, $commandRunner = Mockery::spy(CommandRunnerContract::class));

    $path = base_path('tests/stubs/transactions/valid.csv');

    $this->artisan('process', ['spreadsheet' => $path])->assertSuccessful();

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
            'cost_basis' => '16656.83425160697887970615243342516063',
            'proceeds' => '27979.5',
            'difference' => '11322.66574839302112029384756657483937',
        ]),
        'income' => '1000',
        'non_attributable_allowable_cost' => '117',
    ]);

    $this->assertDatabaseHas('summaries', [
        'currency' => FiatCurrency::GBP->value,
        'fiat_balance' => '3330',
    ]);

    $commandRunner->shouldHaveReceived('run')
        ->withArgs(fn (string $command, array $output) => $command === 'review')
        ->once();
});
