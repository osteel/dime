<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class NonFungibleAssetDisposedOf implements SerializablePayload
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasis,
        public FiatAmount $proceeds,
    ) {
    }

    /** @return array{date:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string}} */
    public function toPayload(): array
    {
        return [
            'date' => (string) $this->date,
            'cost_basis' => $this->costBasis->toPayload(),
            'proceeds' => $this->proceeds->toPayload(),
        ];
    }

    /** @param array{date:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            LocalDate::parse($payload['date']),
            FiatAmount::fromPayload($payload['cost_basis']),
            FiatAmount::fromPayload($payload['proceeds']),
        );
    }
}
