<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final readonly class IncomeUpdated
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $incomeUpdate,
        public FiatAmount $newIncome,
    ) {
    }
}
