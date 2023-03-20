<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class IncomeUpdated implements SerializablePayload
{
    public function __construct(
        public readonly string $taxYear,
        public readonly LocalDate $date,
        public readonly FiatAmount $income,
    ) {
    }

    /** @return array{tax_year:string,date:string,income:array{quantity:string,currency:string}} */
    public function toPayload(): array
    {
        return [
            'tax_year' => $this->taxYear,
            'date' => $this->date->__toString(),
            'income' => $this->income->toPayload(),
        ];
    }

    /** @param array{tax_year:string,date:string,income:array{quantity:string,currency:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            $payload['tax_year'],
            LocalDate::parse($payload['date']),
            FiatAmount::fromPayload($payload['income']),
        );
    }
}
