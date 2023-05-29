<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Stringable;

final readonly class Fee implements Stringable
{
    public function __construct(
        public Asset $currency,
        public Quantity $quantity,
        public FiatAmount $marketValue,
    ) {
    }

    public function isFiat(): bool
    {
        return $this->currency->isFiat();
    }

    public function __toString(): string
    {
        return sprintf('%s %s (market value: %s)', $this->currency, $this->quantity, $this->marketValue);
    }
}
