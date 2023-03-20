<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\ValueObjects;

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Serialization\SerializablePayload;
use JsonSerializable;

final readonly class CapitalGain implements JsonSerializable, SerializablePayload
{
    public FiatAmount $difference;

    public function __construct(
        public FiatAmount $costBasis,
        public FiatAmount $proceeds,
    ) {
        $this->difference = $proceeds->minus($costBasis);
    }

    public function opposite(): self
    {
        return new self($this->costBasis->opposite(), $this->proceeds->opposite());
    }

    public function isGain(): bool
    {
        return $this->difference->isPositive();
    }

    public function isLoss(): bool
    {
        return $this->difference->isNegative();
    }

    public function isEqualTo(CapitalGain $capitalGain): bool
    {
        return $this->costBasis->isEqualTo($capitalGain->costBasis)
            && $this->proceeds->isEqualTo($capitalGain->proceeds)
            && $this->difference->isEqualTo($capitalGain->difference);
    }

    public function currency(): FiatCurrency
    {
        return $this->difference->currency;
    }

    /** @return array{cost_basis:string,proceeds:string,difference:string} */
    public function jsonSerialize(): array
    {
        return [
            'cost_basis' => (string) $this->costBasis->quantity,
            'proceeds' => (string) $this->proceeds->quantity,
            'difference' => (string) $this->difference->quantity,
        ];
    }

    /** @return array{cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},difference:array{quantity:string,currency:string}} */
    public function toPayload(): array
    {
        return [
            'cost_basis' => $this->costBasis->toPayload(),
            'proceeds' => $this->proceeds->toPayload(),
            'difference' => $this->difference->toPayload(),
        ];
    }

    /** @param array{cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string}} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            costBasis: FiatAmount::fromPayload($payload['cost_basis']),
            proceeds: FiatAmount::fromPayload($payload['proceeds']),
        );
    }
}
