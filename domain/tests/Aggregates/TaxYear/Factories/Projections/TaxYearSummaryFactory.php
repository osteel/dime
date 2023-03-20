<?php

namespace Domain\Tests\Aggregates\TaxYear\Factories\Projections;

use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxYearSummary>
 */
class TaxYearSummaryFactory extends Factory
{
    /** @var string */
    protected $model = TaxYearSummary::class;

    /** @return array */
    public function definition()
    {
        return [
            'tax_year_id' => TaxYearId::fromTaxYear('2022-2023'),
            'tax_year' => '2022-2023',
            'currency' => FiatCurrency::GBP,
            'capital_gain' => new CapitalGain(
                costBasis: FiatAmount::GBP('100'),
                proceeds: FiatAmount::GBP('100'),
            ),
            'income' => FiatAmount::GBP('100'),
            'non_attributable_allowable_cost' => FiatAmount::GBP('100'),
        ];
    }
}
