<?php

declare(strict_types=1);

namespace App\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository as TaxYearSummaryRepositoryInterface;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;

class TaxYearSummaryRepository implements TaxYearSummaryRepositoryInterface
{
    public function updateCapitalGain(TaxYearId $taxYearId, CapitalGain $capitalGain): void
    {
        $this->fetchTaxYearSummary($taxYearId, $capitalGain->currency())
            ->updateCapitalGain($capitalGain)
            ->save();
    }

    public function updateIncome(TaxYearId $taxYearId, FiatAmount $income): void
    {
        $this->fetchTaxYearSummary($taxYearId, $income->currency)
            ->updateIncome($income)
            ->save();
    }

    public function updateNonAttributableAllowableCost(TaxYearId $taxYearId, FiatAmount $nonAttributableAllowableCost): void
    {
        $this->fetchTaxYearSummary($taxYearId, $nonAttributableAllowableCost->currency)
            ->updateNonAttributableAllowableCost($nonAttributableAllowableCost)
            ->save();
    }

    private function fetchTaxYearSummary(TaxYearId $taxYearId, FiatCurrency $currency): TaxYearSummary
    {
        return TaxYearSummary::firstOrNew(['tax_year_id' => $taxYearId->toString(), 'currency' => $currency]);
    }
}
