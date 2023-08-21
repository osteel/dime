<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;

final readonly class CapitalGainUpdated
{
    final public function __construct(
        public LocalDate $date,
        public CapitalGain $capitalGainUpdate,
        public CapitalGain $newCapitalGain,
    ) {
    }
}
