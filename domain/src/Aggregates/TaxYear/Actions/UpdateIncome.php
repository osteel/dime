<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final class UpdateIncome implements Stringable
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $income,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, income: %s)',
            self::class,
            (string) $this->date,
            (string) $this->income,
        );
    }
}
