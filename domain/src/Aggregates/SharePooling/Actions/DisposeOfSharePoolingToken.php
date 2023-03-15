<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final readonly class DisposeOfSharePoolingToken
{
    public function __construct(
        public LocalDate $date,
        public Quantity $quantity,
        public FiatAmount $proceeds,
        public ?int $position = null,
    ) {
    }
}
