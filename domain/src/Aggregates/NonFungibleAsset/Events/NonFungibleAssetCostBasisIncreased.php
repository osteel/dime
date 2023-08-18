<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final readonly class NonFungibleAssetCostBasisIncreased
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasisIncrease,
        public FiatAmount $newCostBasis,
        public bool $forFiat,
    ) {
    }
}
