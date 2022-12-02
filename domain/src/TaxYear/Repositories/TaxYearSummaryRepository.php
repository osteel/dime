<?php

declare(strict_types=1);

namespace Domain\TaxYear\Repositories;

use Domain\TaxYear\TaxYearId;
use Domain\ValueObjects\FiatAmount;

interface TaxYearSummaryRepository
{
    public function recordCapitalGain(TaxYearId $taxYearId, FiatAmount $amount): void;

    public function revertCapitalGain(TaxYearId $taxYearId, FiatAmount $amount): void;

    public function recordCapitalLoss(TaxYearId $taxYearId, FiatAmount $amount): void;

    public function revertCapitalLoss(TaxYearId $taxYearId, FiatAmount $amount): void;

    public function recordIncome(TaxYearId $taxYearId, FiatAmount $amount): void;

    public function recordNonAttributableAllowableCost(TaxYearId $taxYearId, FiatAmount $amount): void;
}
