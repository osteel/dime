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

it('matches HMRC cryptoassets manual example CRYPTO22251', function () {
    $this->instance(CommandRunnerContract::class, $commandRunner = Mockery::spy(CommandRunnerContract::class));

    $path = base_path('tests/stubs/transactions/CRYPTO22251.csv');

    $this->artisan('process', ['spreadsheet' => $path])->assertSuccessful();

    $this->assertDatabaseCount('tax_year_summaries', 1);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2023-2024',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '42000',
            'proceeds' => '300000',
            'difference' => '258000',
        ]),
        'income' => '0',
        'non_attributable_allowable_cost' => '0',
    ]);

    $this->assertDatabaseHas('summaries', [
        'currency' => FiatCurrency::GBP->value,
        'fiat_balance' => '174000',
    ]);

    $commandRunner->shouldHaveReceived('run')
        ->withArgs(fn (string $command, array $output) => $command === 'review')
        ->once();
});

it('matches HMRC cryptoassets manual example CRYPTO22252', function () {
    $this->instance(CommandRunnerContract::class, $commandRunner = Mockery::spy(CommandRunnerContract::class));

    $path = base_path('tests/stubs/transactions/CRYPTO22252.csv');

    $this->artisan('process', ['spreadsheet' => $path])->assertSuccessful();

    $this->assertDatabaseCount('tax_year_summaries', 1);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2023-2024',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '937.5',
            'proceeds' => '1400',
            'difference' => '462.5',
        ]),
        'income' => '0',
        'non_attributable_allowable_cost' => '0',
    ]);

    $this->assertDatabaseHas('summaries', [
        'currency' => FiatCurrency::GBP->value,
        'fiat_balance' => '-100',
    ]);

    $commandRunner->shouldHaveReceived('run')
        ->withArgs(fn (string $command, array $output) => $command === 'review')
        ->once();
});

it('matches HMRC cryptoassets manual example CRYPTO22253', function () {
    $this->instance(CommandRunnerContract::class, $commandRunner = Mockery::spy(CommandRunnerContract::class));

    $path = base_path('tests/stubs/transactions/CRYPTO22253.csv');

    $this->artisan('process', ['spreadsheet' => $path])->assertSuccessful();

    $this->assertDatabaseCount('tax_year_summaries', 2);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2022-2023',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '235',
            'proceeds' => '400',
            'difference' => '165',
        ]),
        'income' => '0',
        'non_attributable_allowable_cost' => '0',
    ]);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2023-2024',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '130',
            'proceeds' => '150',
            'difference' => '20',
        ]),
        'income' => '0',
        'non_attributable_allowable_cost' => '0',
    ]);

    $this->assertDatabaseHas('summaries', [
        'currency' => FiatCurrency::GBP->value,
        'fiat_balance' => '-875',
    ]);

    $commandRunner->shouldHaveReceived('run')
        ->withArgs(fn (string $command, array $output) => $command === 'review')
        ->once();
});

it('matches HMRC cryptoassets manual example CRYPTO22254', function () {
    $this->instance(CommandRunnerContract::class, $commandRunner = Mockery::spy(CommandRunnerContract::class));

    $path = base_path('tests/stubs/transactions/CRYPTO22254.csv');

    $this->artisan('process', ['spreadsheet' => $path])->assertSuccessful();

    $this->assertDatabaseCount('tax_year_summaries', 1);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2023-2024',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '67499.99999999999999999999999999998',
            'proceeds' => '160000',
            'difference' => '92500.00000000000000000000000000002',
        ]),
        'income' => '0',
        'non_attributable_allowable_cost' => '0',
    ]);

    $this->assertDatabaseHas('summaries', [
        'currency' => FiatCurrency::GBP->value,
        'fiat_balance' => '-57500',
    ]);

    $commandRunner->shouldHaveReceived('run')
        ->withArgs(fn (string $command, array $output) => $command === 'review')
        ->once();
});

it('matches HMRC cryptoassets manual example CRYPTO22255', function () {
    $this->instance(CommandRunnerContract::class, $commandRunner = Mockery::spy(CommandRunnerContract::class));

    $path = base_path('tests/stubs/transactions/CRYPTO22255.csv');

    $this->artisan('process', ['spreadsheet' => $path])->assertSuccessful();

    $this->assertDatabaseCount('tax_year_summaries', 1);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2022-2023',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '562.499999999999999999999999999955',
            'proceeds' => '642',
            'difference' => '79.500000000000000000000000000045',
        ]),
        'income' => '0',
        'non_attributable_allowable_cost' => '0',
    ]);

    $this->assertDatabaseHas('summaries', [
        'currency' => FiatCurrency::GBP->value,
        'fiat_balance' => '-858',
    ]);

    $commandRunner->shouldHaveReceived('run')
        ->withArgs(fn (string $command, array $output) => $command === 'review')
        ->once();
});

it('matches HMRC cryptoassets manual example CRYPTO22256', function () {
    $this->instance(CommandRunnerContract::class, $commandRunner = Mockery::spy(CommandRunnerContract::class));

    $path = base_path('tests/stubs/transactions/CRYPTO22256.csv');

    $this->artisan('process', ['spreadsheet' => $path])->assertSuccessful();

    $this->assertDatabaseCount('tax_year_summaries', 1);

    $this->assertDatabaseHas('tax_year_summaries', [
        'tax_year_id' => '2023-2024',
        'currency' => FiatCurrency::GBP->value,
        'capital_gain' => json_encode([
            'cost_basis' => '538636.363636363636363636363636363',
            'proceeds' => '400000',
            'difference' => '-138636.363636363636363636363636363',
        ]),
        'income' => '0',
        'non_attributable_allowable_cost' => '0',
    ]);

    $this->assertDatabaseHas('summaries', [
        'currency' => FiatCurrency::GBP->value,
        'fiat_balance' => '-170000',
    ]);

    $commandRunner->shouldHaveReceived('run')
        ->withArgs(fn (string $command, array $output) => $command === 'review')
        ->once();
});
