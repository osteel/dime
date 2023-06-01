<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\Projections\TaxYearSummary;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\ValueObjects\FiatAmount;

interface TaxYearSummaryRepository
{
    public function find(TaxYearId $taxYearId): ?TaxYearSummary;

    /** @return list<TaxYearSummary> */
    public function all(): array;

    public function updateCapitalGain(TaxYearId $taxYearId, CapitalGain $capitalGain): void;

    public function updateIncome(TaxYearId $taxYearId, FiatAmount $income): void;

    public function updateNonAttributableAllowableCost(TaxYearId $taxYearId, FiatAmount $nonAttributableAllowableCost): void;
}
