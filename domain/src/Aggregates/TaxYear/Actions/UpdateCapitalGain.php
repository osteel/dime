<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Stringable;

final class UpdateCapitalGain implements Stringable
{
    final public function __construct(
        public readonly LocalDate $date,
        public readonly CapitalGain $capitalGain,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, capital gain: (%s))',
            self::class,
            (string) $this->date,
            (string) $this->capitalGain,
        );
    }
}
