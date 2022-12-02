<?php

declare(strict_types=1);

namespace App\TaxYear;

use Domain\TaxYear\Projections\TaxYearSummary;
use Domain\TaxYear\Repositories\TaxYearSummaryRepository as TaxYearSummaryRepositoryInterface;
use Domain\TaxYear\TaxYearId;
use Domain\ValueObjects\FiatAmount;

class TaxYearSummaryRepository implements TaxYearSummaryRepositoryInterface
{
    public function recordCapitalGain(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->increaseCapitalGain($amount)
            ->save();
    }

    public function revertCapitalGain(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->decreaseCapitalGain($amount)
            ->save();
    }

    public function recordCapitalLoss(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->decreaseCapitalGain($amount)
            ->save();
    }

    public function revertCapitalLoss(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->increaseCapitalGain($amount)
            ->save();
    }

    public function recordIncome(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->increaseIncome($amount)
            ->save();
    }

    public function recordNonAttributableAllowableCost(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void
    {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->increaseNonAttributableAllowableCosts($amount)
            ->save();
    }

    private function fetchTaxYearSummary(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): TaxYearSummary
    {
        return TaxYearSummary::firstOrNew(
            ['tax_year_id' => $taxYearId->toString(), 'currency' => $amount->currency],
            ['tax_year' => $taxYear],
        );
    }
}
