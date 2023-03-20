<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;

final class RevertCapitalGainUpdate
{
    final public function __construct(
        public readonly string $taxYear,
        public readonly LocalDate $date,
        public readonly CapitalGain $capitalGain,
    ) {
    }
}
