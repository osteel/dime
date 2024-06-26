<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final readonly class NonFungibleAssetDisposedOf
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasis,
        public FiatAmount $proceeds,
        public bool $forFiat,
    ) {
    }
}
