<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use App\Services\ObjectHydrators\CapitalGainHydrator;
use App\Services\ObjectHydrators\LocalDateHydrator;
use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;

final class CapitalGainUpdateReverted
{
    final public function __construct(
        #[LocalDateHydrator]
        public readonly LocalDate $date,
        #[CapitalGainHydrator]
        public readonly CapitalGain $capitalGain,
    ) {
    }
}
