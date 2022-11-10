<?php

namespace Domain\SharePooling\Actions;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\SharePoolingId;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final class DisposeOfSharePoolingToken
{
    public function __construct(
        public readonly SharePoolingId $sharePoolingId,
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $disposalProceeds,
        public readonly ?int $position = null,
    ) {
    }
}