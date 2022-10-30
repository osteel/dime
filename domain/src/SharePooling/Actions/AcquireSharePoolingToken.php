<?php

namespace Domain\SharePooling\Actions;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\SharePoolingId;
use Domain\ValueObjects\FiatAmount;

final class AcquireSharePoolingToken
{
    public function __construct(
        public readonly SharePoolingId $sharePoolingId,
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
    ) {
    }
}
