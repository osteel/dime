<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final readonly class AcquireNonFungibleAsset
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasis,
    ) {
    }
}
