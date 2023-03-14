<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\ValueObjects\FiatAmount;

interface TaxYearSummaryRepository
{
    public function recordCapitalGain(
        TaxYearId $taxYearId,
        string $taxYear,
        FiatAmount $amount,
        FiatAmount $costBasis,
        FiatAmount $proceeds,
    ): void;

    public function revertCapitalGain(
        TaxYearId $taxYearId,
        string $taxYear,
        FiatAmount $amount,
        FiatAmount $costBasis,
        FiatAmount $proceeds,
    ): void;

    public function recordCapitalLoss(
        TaxYearId $taxYearId,
        string $taxYear,
        FiatAmount $amount,
        FiatAmount $costBasis,
        FiatAmount $proceeds,
    ): void;

    public function revertCapitalLoss(
        TaxYearId $taxYearId,
        string $taxYear,
        FiatAmount $amount,
        FiatAmount $costBasis,
        FiatAmount $proceeds,
    ): void;

    public function recordIncome(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void;

    public function recordNonAttributableAllowableCost(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void;
}
