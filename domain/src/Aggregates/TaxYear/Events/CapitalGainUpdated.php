<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;

final class CapitalGainUpdated
{
    final public function __construct(
        public readonly LocalDate $date,
        public readonly CapitalGain $capitalGainUpdate,
        public readonly CapitalGain $newCapitalGain,
    ) {
    }
}
