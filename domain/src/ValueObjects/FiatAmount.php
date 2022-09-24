<?php

namespace Domain\ValueObjects;

use Domain\Enums\Currency;

final class FiatAmount
{
    public function __construct(
        public readonly string $amount,
        public readonly Currency $currency,
    ) {
    }
}
