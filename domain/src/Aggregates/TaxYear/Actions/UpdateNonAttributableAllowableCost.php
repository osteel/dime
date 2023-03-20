<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final class UpdateNonAttributableAllowableCost
{
    public function __construct(
        public readonly string $taxYear,
        public readonly LocalDate $date,
        public readonly FiatAmount $nonAttributableAllowableCost,
    ) {
    }
}
