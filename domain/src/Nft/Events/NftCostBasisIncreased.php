<?php

declare(strict_types=1);

namespace Domain\Nft\Events;

use Domain\Nft\NftId;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class NftCostBasisIncreased implements SerializablePayload
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $costBasisIncrease,
    ) {
    }

    /** @return array<string, string|array<string, string>> */
    public function toPayload(): array
    {
        return [
            'nft_id' => $this->nftId->id,
            'cost_basis_increase' => $this->costBasisIncrease->toArray(),
        ];
    }

    /** @param array<string, string|array<string, string>> $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            NftId::fromString($payload['nft_id']), // @phpstan-ignore-line
            FiatAmount::fromArray($payload['cost_basis_increase']), // @phpstan-ignore-line
        );
    }
}
