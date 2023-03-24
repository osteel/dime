<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\Nft\Actions\Contracts\Timely;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final readonly class IncreaseNftCostBasis implements Stringable, Timely
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasisIncrease,
    ) {
    }

    public function getDate(): LocalDate
    {
        return $this->date;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, cost basis increase: %s)',
            self::class,
            (string) $this->date,
            (string) $this->costBasisIncrease,
        );
    }
}
