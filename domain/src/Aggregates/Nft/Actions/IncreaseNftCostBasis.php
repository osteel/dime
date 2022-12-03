<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final class IncreaseNftCostBasis
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $costBasisIncrease,
    ) {
    }
}