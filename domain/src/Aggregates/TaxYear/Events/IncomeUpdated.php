<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class IncomeUpdated implements SerializablePayload
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $income,
    ) {
    }

    /** @return array{date:string,income:array{quantity:string,currency:string}} */
    public function toPayload(): array
    {
        return [
            'date' => (string) $this->date,
            'income' => $this->income->toPayload(),
        ];
    }

    /** @param array{date:string,income:array{quantity:string,currency:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            LocalDate::parse($payload['date']),
            FiatAmount::fromPayload($payload['income']),
        );
    }
}
