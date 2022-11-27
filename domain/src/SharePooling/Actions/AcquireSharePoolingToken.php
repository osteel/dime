<?php

declare(strict_types=1);

namespace Domain\SharePooling\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final class AcquireSharePoolingToken
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
    ) {
    }
}
