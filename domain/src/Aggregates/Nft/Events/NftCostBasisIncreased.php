<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class NftCostBasisIncreased implements SerializablePayload
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $costBasisIncrease,
    ) {
    }

    /** @return array<string, string|array<string, string>> */
    public function toPayload(): array
    {
        return [
            'date' => $this->date->__toString(),
            'cost_basis_increase' => $this->costBasisIncrease->toPayload(),
        ];
    }

    /** @param array<string, string|array<string, string>> $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            LocalDate::parse($payload['date']), // @phpstan-ignore-line
            FiatAmount::fromPayload($payload['cost_basis_increase']), // @phpstan-ignore-line
        );
    }
}
