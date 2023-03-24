<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\Actions\Contracts\Timely;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Stringable;

final readonly class AcquireSharePoolingToken implements Stringable, Timely
{
    public function __construct(
        public LocalDate $date,
        public Quantity $quantity,
        public FiatAmount $costBasis,
    ) {
    }

    public function getDate(): LocalDate
    {
        return $this->date;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, quantity: %s, cost basis: %s)',
            self::class,
            (string) $this->date,
            (string) $this->quantity,
            (string) $this->costBasis,
        );
    }
}
