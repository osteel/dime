<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\ValueObjects;

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use JsonSerializable;
use Stringable;

final readonly class CapitalGain implements JsonSerializable, Stringable
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

    public function plus(CapitalGain $capitalGain): self
    {
        return new self(
            costBasis: $this->costBasis->plus($capitalGain->costBasis),
            proceeds: $this->proceeds->plus($capitalGain->proceeds),
        );
    }

    public function minus(CapitalGain $capitalGain): self
    {
        return new self(
            costBasis: $this->costBasis->minus($capitalGain->costBasis),
            proceeds: $this->proceeds->minus($capitalGain->proceeds),
        );
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

    public function __toString(): string
    {
        return sprintf('cost basis: %s, proceeds: %s', $this->costBasis, $this->proceeds);
    }
}
