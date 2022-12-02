<?php

declare(strict_types=1);

namespace Domain\TaxYear\Events;

use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

abstract class TaxYearEvent implements SerializablePayload
{
    final public function __construct(
        public readonly string $taxYear,
        public readonly FiatAmount $amount,
    ) {
    }

    /** @return array<string, string|array<string, string>> */
    public function toPayload(): array
    {
        return [
            'tax_year' => $this->taxYear,
            'amount' => $this->amount->toPayload(),
        ];
    }

    /** @param array<string, string|array<string, string>> $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            $payload['tax_year'], // @phpstan-ignore-line
            FiatAmount::fromPayload($payload['amount']), // @phpstan-ignore-line
        );
    }
}
