<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Events;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;

abstract class CapitalEvent extends TaxYearEvent
{
    final public function __construct(
        public readonly string $taxYear,
        public readonly LocalDate $date,
        public readonly FiatAmount $amount,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $proceeds,
    ) {
    }

    /** @return array<string, string|array<string, string>> */
    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'cost_basis' => $this->costBasis->toPayload(),
            'proceeds' => $this->proceeds->toPayload(),
        ]);
    }

    /** @param array<string, string|array<string, string>> $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            $payload['tax_year'], // @phpstan-ignore-line
            LocalDate::parse($payload['date']), // @phpstan-ignore-line
            FiatAmount::fromPayload($payload['amount']), // @phpstan-ignore-line
            FiatAmount::fromPayload($payload['cost_basis']), // @phpstan-ignore-line
            FiatAmount::fromPayload($payload['proceeds']), // @phpstan-ignore-line
        );
    }
}
