<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use Domain\Enums\FiatCurrency;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingAssetFiatCurrencySet implements SerializablePayload
{
    public function __construct(
        public FiatCurrency $fiatCurrency,
    ) {
    }

    /** @return array{fiat_currency:string} */
    public function toPayload(): array
    {
        return ['fiat_currency' => $this->fiatCurrency->value];
    }

    /** @param array{fiat_currency:string} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(FiatCurrency::from($payload['fiat_currency']));
    }
}
