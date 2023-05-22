<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class NonAttributableAllowableCostUpdated implements SerializablePayload
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $nonAttributableAllowableCost,
    ) {
    }

    /** @return array{date:string,non_attributable_allowable_cost:array{quantity:string,currency:string}} */
    public function toPayload(): array
    {
        return [
            'date' => (string) $this->date,
            'non_attributable_allowable_cost' => $this->nonAttributableAllowableCost->toPayload(),
        ];
    }

    /** @param array{date:string,non_attributable_allowable_cost:array{quantity:string,currency:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            LocalDate::parse($payload['date']),
            FiatAmount::fromPayload($payload['non_attributable_allowable_cost']),
        );
    }
}
