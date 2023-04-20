<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\Contracts\Timely;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Stringable;

final readonly class DisposeOfSharePoolingAsset implements Stringable, Timely
{
    public function __construct(
        public LocalDate $date,
        public Quantity $quantity,
        public FiatAmount $proceeds,
        // Only present whenever the disposal has been reverted and is now being replayed
        public ?SharePoolingAssetTransactionId $id = null,
    ) {
    }

    public function isReplay(): bool
    {
        return ! is_null($this->id);
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
