<?php

declare(strict_types=1);

namespace Domain\TaxYear\Repositories;

use Domain\TaxYear\TaxYearId;
use Generator;

interface TaxYearMessageRepository
{
    public function all(TaxYearId $taxYearId): Generator;
}
