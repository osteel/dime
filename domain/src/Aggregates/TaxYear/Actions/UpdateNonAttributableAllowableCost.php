<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final class UpdateNonAttributableAllowableCost implements Stringable
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $nonAttributableAllowableCost,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, non-attributable allowable cost: %s)',
            self::class,
            (string) $this->date,
            (string) $this->nonAttributableAllowableCost,
        );
    }
}
