<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Domain\Enums\FiatCurrency;

final readonly class Fee
{
    public function __construct(
        public FiatCurrency | AssetSymbol $currency,
        public Quantity $quantity,
        public FiatAmount $marketValue,
    ) {
    }

    public function isFiat(): bool
    {
        return $this->currency instanceof FiatCurrency;
    }
}
