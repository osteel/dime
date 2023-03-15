<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final readonly class AcquireNft
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasis,
    ) {
    }
}
