<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final readonly class AcquireSharePoolingToken
{
    public function __construct(
        public LocalDate $date,
        public Quantity $quantity,
        public FiatAmount $costBasis,
    ) {
    }
}
