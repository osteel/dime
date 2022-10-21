<?php

namespace Domain\ValueObjects;

use Domain\Enums\FiatCurrency;

final class FiatAmount
{
    public function __construct(
        public readonly string $amount,
        public readonly FiatCurrency $currency,
    ) {
    }
}
