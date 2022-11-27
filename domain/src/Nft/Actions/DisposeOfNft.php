<?php

declare(strict_types=1);

namespace Domain\Nft\Actions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

final class DisposeOfNft
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $proceeds,
    ) {
    }
}
