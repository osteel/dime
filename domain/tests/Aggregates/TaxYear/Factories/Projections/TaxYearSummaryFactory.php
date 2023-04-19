<?php

namespace Domain\Tests\Aggregates\TaxYear\Factories\Projections;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxYearSummary> */
class TaxYearSummaryFactory extends Factory
{
    /** @var string */
    protected $model = TaxYearSummary::class;

    /** @return array */
    public function definition()
    {
        return [
            'tax_year_id' => TaxYearId::fromDate(LocalDate::parse('2015-10-21')),
            'tax_year' => '2015-2016',
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
