<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

abstract class CapitalAction extends TaxYearAction
{
    final public function __construct(
        public readonly string $taxYear,
        public readonly LocalDate $date,
        public readonly FiatAmount $amount,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $proceeds,
    ) {
    }
}
