<?php

declare(strict_types=1);

namespace App\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository as TaxYearSummaryRepositoryInterface;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;

class TaxYearSummaryRepository implements TaxYearSummaryRepositoryInterface
{
    public function updateCapitalGain(TaxYearId $taxYearId, string $taxYear, CapitalGain $capitalGain): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $capitalGain->currency())
            ->updateCapitalGain($capitalGain)
            ->save();
    }

    public function updateIncome(TaxYearId $taxYearId, string $taxYear, FiatAmount $income): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $income->currency)
            ->updateIncome($income)
            ->save();
    }

    public function updateNonAttributableAllowableCost(TaxYearId $taxYearId, string $taxYear, FiatAmount $nonAttributableAllowableCost): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $nonAttributableAllowableCost->currency)
            ->updateNonAttributableAllowableCosts($nonAttributableAllowableCost)
            ->save();
    }

    private function fetchTaxYearSummary(TaxYearId $taxYearId, string $taxYear, FiatCurrency $currency): TaxYearSummary
    {
        return TaxYearSummary::firstOrNew(
            ['tax_year_id' => $taxYearId->toString(), 'currency' => $currency],
            ['tax_year' => $taxYear],
        );
    }
}
