<?php

declare(strict_types=1);

namespace Domain\TaxYear\Actions;

use Domain\ValueObjects\FiatAmount;

abstract class TaxYearAction
{
    final public function __construct(
        public readonly string $taxYear,
        public readonly FiatAmount $amount,
    ) {
    }
}
