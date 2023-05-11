<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class NonFungibleAssetDisposedOf implements SerializablePayload
{
    public function __construct(
        public Asset $asset,
        public LocalDate $date,
        public FiatAmount $costBasis,
        public FiatAmount $proceeds,
    ) {
    }

    /** @return array{asset:array{symbol:string,is_non_fungible:string},date:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string}} */
    public function toPayload(): array
    {
        return [
            'asset' => $this->asset->toPayload(),
            'date' => (string) $this->date,
            'cost_basis' => $this->costBasis->toPayload(),
            'proceeds' => $this->proceeds->toPayload(),
        ];
    }

    /** @param array{asset:array{symbol:string,is_non_fungible:string},date:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            Asset::fromPayload($payload['asset']),
            LocalDate::parse($payload['date']),
            FiatAmount::fromPayload($payload['cost_basis']),
            FiatAmount::fromPayload($payload['proceeds']),
        );
    }
}
