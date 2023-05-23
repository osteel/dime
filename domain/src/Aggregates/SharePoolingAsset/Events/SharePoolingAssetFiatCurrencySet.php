<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use Domain\Enums\FiatCurrency;

final readonly class SharePoolingAssetFiatCurrencySet
{
    public function __construct(
        public FiatCurrency $fiatCurrency,
    ) {
    }
}
