<?php

declare(strict_types=1);

namespace Domain\TaxYear\Events;

use Domain\TaxYear\TaxYearId;
use Domain\ValueObjects\FiatAmount;

final class NonAttributableAllowableCostRecorded
{
    public function __construct(
        public readonly TaxYearId $taxYearId,
        public readonly FiatAmount $amount,
    ) {
    }
}
