<?php

declare(strict_types=1);

namespace App\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository as TaxYearSummaryRepositoryInterface;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\ValueObjects\FiatAmount;

class TaxYearSummaryRepository implements TaxYearSummaryRepositoryInterface
{
    public function recordCapitalGain(
        TaxYearId $taxYearId,
        string $taxYear,
        FiatAmount $amount,
        FiatAmount $costBasis,
        FiatAmount $proceeds,
    ): void {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->increaseCapitalGain($amount)
            ->increaseCapitalCostBasis($costBasis)
            ->increaseCapitalProceeds($proceeds)
            ->save();
    }

    public function revertCapitalGain(
        TaxYearId $taxYearId,
        string $taxYear,
        FiatAmount $amount,
        FiatAmount $costBasis,
        FiatAmount $proceeds,
    ): void {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->decreaseCapitalGain($amount)
            ->decreaseCapitalCostBasis($costBasis)
            ->decreaseCapitalProceeds($proceeds)
            ->save();
    }

    public function recordCapitalLoss(
        TaxYearId $taxYearId,
        string $taxYear,
        FiatAmount $amount,
        FiatAmount $costBasis,
        FiatAmount $proceeds,
    ): void {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->decreaseCapitalGain($amount)
            ->increaseCapitalCostBasis($costBasis)
            ->increaseCapitalProceeds($proceeds)
            ->save();
    }

    public function revertCapitalLoss(
        TaxYearId $taxYearId,
        string $taxYear,
        FiatAmount $amount,
        FiatAmount $costBasis,
        FiatAmount $proceeds,
    ): void {
        $this->fetchTaxYearSummary($taxYearId, $taxYear, $amount)
            ->increaseCapitalGain($amount)
            ->decreaseCapitalCostBasis($costBasis)
            ->decreaseCapitalProceeds($proceeds)
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
