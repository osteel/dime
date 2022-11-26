<?php

declare(strict_types=1);

namespace Domain\TaxYear\Repositories;

use Domain\TaxYear\TaxYear;
use Domain\TaxYear\TaxYearId;

interface TaxYearRepository
{
    public function get(TaxYearId $taxYearId): TaxYear;

    public function save(TaxYearId $taxYearId): TaxYear;
}
