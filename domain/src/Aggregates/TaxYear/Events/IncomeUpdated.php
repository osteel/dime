<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final class IncomeUpdated
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $income,
    ) {
    }
}
