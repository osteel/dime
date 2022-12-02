<?php

declare(strict_types=1);

namespace Domain\TaxYear\Repositories;

use Domain\TaxYear\TaxYearId;
use Domain\ValueObjects\FiatAmount;

interface TaxYearSummaryRepository
{
    public function recordCapitalGain(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void;

    public function revertCapitalGain(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void;

    public function recordCapitalLoss(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void;

    public function revertCapitalLoss(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void;

    public function recordIncome(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void;

    public function recordNonAttributableAllowableCost(TaxYearId $taxYearId, string $taxYear, FiatAmount $amount): void;
}
