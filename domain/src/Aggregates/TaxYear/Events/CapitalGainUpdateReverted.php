<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class CapitalGainUpdateReverted implements SerializablePayload
{
    final public function __construct(
        public readonly LocalDate $date,
        public readonly CapitalGain $capitalGain,
    ) {
    }

    /** @return array{date:string,capital_gain:array{cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},difference:array{quantity:string,currency:string}}} */
    public function toPayload(): array
    {
        return [
            'date' => (string) $this->date,
            'capital_gain' => $this->capitalGain->toPayload(),
        ];
    }

    /** @param array{date:string,capital_gain:array{cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},difference:array{quantity:string,currency:string}}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            LocalDate::parse($payload['date']),
            CapitalGain::fromPayload($payload['capital_gain']),
        );
    }
}
