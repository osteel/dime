<?php

use App\Commands\Review;
use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;

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
        ->expectsTable(
            Review::SUMMARY_HEADERS,
            [['£200.00', '£100.00', '£75.00', '£175.00', '£25.00', '£50.00']],
        )
        ->assertSuccessful();
});
