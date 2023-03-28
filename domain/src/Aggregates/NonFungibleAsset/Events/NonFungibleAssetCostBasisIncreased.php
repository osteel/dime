<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class NonFungibleAssetCostBasisIncreased implements SerializablePayload
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasisIncrease,
    ) {
    }

    /** @return array{date:string,cost_basis_increase:array{quantity:string,currency:string}} */
    public function toPayload(): array
    {
        return [
            'date' => $this->date->__toString(),
            'cost_basis_increase' => $this->costBasisIncrease->toPayload(),
        ];
    }

    /** @param array{date:string,cost_basis_increase:array{quantity:string,currency:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            LocalDate::parse($payload['date']),
            FiatAmount::fromPayload($payload['cost_basis_increase']),
        );
    }
}
