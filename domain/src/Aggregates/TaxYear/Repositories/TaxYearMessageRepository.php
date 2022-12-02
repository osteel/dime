<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\TaxYearId;
use Generator;

interface TaxYearMessageRepository
{
    public function all(TaxYearId $taxYearId): Generator;
}
