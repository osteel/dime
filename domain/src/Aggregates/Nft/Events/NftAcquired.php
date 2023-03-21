<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class NftAcquired implements SerializablePayload
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasis,
    ) {
    }

    /** @return array{date:string,cost_basis:array{quantity:string,currency:string}} */
    public function toPayload(): array
    {
        return [
            'date' => $this->date->__toString(),
            'cost_basis' => $this->costBasis->toPayload(),
        ];
    }

    /** @param array{date:string,cost_basis:array{quantity:string,currency:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            LocalDate::parse($payload['date']),
            FiatAmount::fromPayload($payload['cost_basis']),
        );
    }
}
