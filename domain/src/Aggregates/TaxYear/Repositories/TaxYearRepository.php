<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\TaxYearContract;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;

interface TaxYearRepository
{
    public function get(TaxYearId $taxYearId): TaxYearContract;

    public function save(TaxYearContract $taxYear): void;
}
