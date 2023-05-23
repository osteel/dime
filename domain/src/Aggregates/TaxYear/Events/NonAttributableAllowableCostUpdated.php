<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use App\Services\ObjectHydrators\FiatAmountHydrator;
use App\Services\ObjectHydrators\LocalDateHydrator;
use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final class NonAttributableAllowableCostUpdated
{
    public function __construct(
        #[LocalDateHydrator]
        public readonly LocalDate $date,
        #[FiatAmountHydrator]
        public readonly FiatAmount $nonAttributableAllowableCost,
    ) {
    }
}
