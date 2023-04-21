<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\ValueObjects\FiatAmount;

interface TaxYearSummaryRepository
{
    public function updateCapitalGain(TaxYearId $taxYearId, string $taxYear, CapitalGain $capitalGain): void;

    public function updateIncome(TaxYearId $taxYearId, string $taxYear, FiatAmount $income): void;

    public function updateNonAttributableAllowableCost(TaxYearId $taxYearId, string $taxYear, FiatAmount $nonAttributableAllowableCost): void;
}
