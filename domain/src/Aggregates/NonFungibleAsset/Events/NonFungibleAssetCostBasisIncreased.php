<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Events;

use App\Services\ObjectHydrators\FiatAmountHydrator;
use App\Services\ObjectHydrators\LocalDateHydrator;
use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final readonly class NonFungibleAssetCostBasisIncreased
{
    public function __construct(
        #[LocalDateHydrator]
        public LocalDate $date,
        #[FiatAmountHydrator]
        public FiatAmount $costBasisIncrease,
    ) {
    }
}
