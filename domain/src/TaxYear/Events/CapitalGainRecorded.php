<?php

declare(strict_types=1);

namespace Domain\TaxYear\Events;

use Domain\ValueObjects\FiatAmount;

final class CapitalGainRecorded
{
    public function __construct(
        public readonly FiatAmount $amount,
    ) {
    }
}
