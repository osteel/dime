<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\Contracts\Timely;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final readonly class DisposeOfNonFungibleAsset implements Stringable, Timely
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $proceeds,
    ) {
    }

    public function getDate(): LocalDate
    {
        return $this->date;
    }

    public function __toString(): string
    {
        return sprintf('%s (date: %s, proceeds: %s)', self::class, (string) $this->date, (string) $this->proceeds);
    }
}
