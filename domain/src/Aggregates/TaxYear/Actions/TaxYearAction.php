<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Domain\ValueObjects\FiatAmount;

abstract class TaxYearAction
{
    final public function __construct(
        public readonly string $taxYear,
        public readonly FiatAmount $amount,
    ) {
    }
}
