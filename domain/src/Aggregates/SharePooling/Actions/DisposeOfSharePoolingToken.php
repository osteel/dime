<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\Actions\Contracts\Timely;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Stringable;

final readonly class DisposeOfSharePoolingToken implements Stringable, Timely
{
    public function __construct(
        public LocalDate $date,
        public Quantity $quantity,
        public FiatAmount $proceeds,
        public ?int $position = null,
    ) {
    }

    public function isReplay(): bool
    {
        return ! is_null($this->position);
    }

    public function getDate(): LocalDate
    {
        return $this->date;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, quantity: %s, proceeds: %s)',
            self::class,
            (string) $this->date,
            (string) $this->quantity,
            (string) $this->proceeds,
        );
    }
}
