<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use App\Services\ObjectHydration\Hydrators\CapitalGainHydrator;
use App\Services\ObjectHydration\Hydrators\LocalDateHydrator;
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
