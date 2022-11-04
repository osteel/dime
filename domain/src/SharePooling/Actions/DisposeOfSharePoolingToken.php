<?php

namespace Domain\SharePooling\Actions;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\SharePoolingId;
use Domain\ValueObjects\FiatAmount;

final class DisposeOfSharePoolingToken
{
    public function __construct(
        public readonly SharePoolingId $sharePoolingId,
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $disposalProceeds,
        public readonly ?int $position = null,
    ) {
    }
}
