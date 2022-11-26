<?php

declare(strict_types=1);

namespace Domain\Nft\Events;

use Domain\Nft\NftId;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class NftDisposedOf implements SerializablePayload
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $proceeds,
    ) {
    }

    /** @return array<string, string|array<string, string>> */
    public function toPayload(): array
    {
        return [
            'nft_id' => $this->nftId->id,
            'cost_basis' => $this->costBasis->toArray(),
            'proceeds' => $this->proceeds->toArray(),
        ];
    }

    /** @param array<string, string|array<string, string>> $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            NftId::fromString($payload['nft_id']), // @phpstan-ignore-line
            FiatAmount::fromArray($payload['cost_basis']), // @phpstan-ignore-line
            FiatAmount::fromArray($payload['proceeds']), // @phpstan-ignore-line
        );
    }
}
