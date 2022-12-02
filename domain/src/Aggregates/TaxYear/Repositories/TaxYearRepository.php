<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\TaxYearId;

interface TaxYearRepository
{
    public function get(TaxYearId $taxYearId): TaxYear;

    public function save(TaxYear $taxYear): self;
}
