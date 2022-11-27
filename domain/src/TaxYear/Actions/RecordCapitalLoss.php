<?php

declare(strict_types=1);

namespace Domain\TaxYear\Actions;

use Domain\ValueObjects\FiatAmount;

final class RecordCapitalLoss
{
    public function __construct(
        public readonly FiatAmount $amount,
    ) {
    }
}
