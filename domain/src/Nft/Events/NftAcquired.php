<?php

declare(strict_types=1);

namespace Domain\Nft\Events;

use Brick\DateTime\LocalDate;
use Domain\Nft\NftId;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class NftAcquired implements SerializablePayload
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly LocalDate $date,
        public readonly FiatAmount $costBasis,
    ) {
    }

    /** @return array<string, string|array<string, string>> */
    public function toPayload(): array
    {
        return [
            'nft_id' => $this->nftId->id,
            'date' => $this->date->__toString(),
            'cost_basis' => $this->costBasis->toArray(),
        ];
    }

    /** @param array<string, string|array<string, string>> $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            NftId::fromString($payload['nft_id']), // @phpstan-ignore-line
            LocalDate::parse($payload['date']), // @phpstan-ignore-line
            FiatAmount::fromArray($payload['cost_basis']), // @phpstan-ignore-line
        );
    }
}
