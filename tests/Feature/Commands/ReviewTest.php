<?php

use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use LaravelZero\Framework\Commands\Command;

it('can review a tax year', function () {
    TaxYearSummary::factory()->create([
        'tax_year_id' => TaxYearId::fromString('2015-2016'),
        'currency' => FiatCurrency::GBP,
        'capital_gain' => new CapitalGain(
            costBasis: FiatAmount::GBP('100'),
            proceeds: FiatAmount::GBP('200'),
        ),
        'income' => FiatAmount::GBP('50'),
        'non_attributable_allowable_cost' => FiatAmount::GBP('75'),
    ]);

    $this->assertDatabaseCount('tax_year_summaries', 1);

    $this->artisan('review')
        ->expectsOutputToContain('---------- ------------ --------------------------------- ------------------ ---------------------- --------')
        ->expectsOutputToContain(' Proceeds   Cost basis   Non-attributable allowable cost   Total cost basis   Capital gain or loss   Income ')
        ->expectsOutputToContain(' £200.00    £100.00      £75.00                            £175.00            £25.00                 £50.00 ')
        ->assertExitCode(Command::SUCCESS);
});
