<?php

namespace Domain\ValueObjects;

use Domain\Enums\FiatCurrency;
use Domain\Services\Math\Math;
use Domain\ValueObjects\Exceptions\FiatAmountException;
use Stringable;

final class FiatAmount implements Stringable
{
    public function __construct(
        public readonly string $amount,
        public readonly FiatCurrency $currency,
    ) {
    }

    public function nilAmount(): FiatAmount
    {
        return new self('0', $this->currency);
    }

    /** @throws FiatAmountException */
    public function plus(FiatAmount | Quantity | string $operand): FiatAmount
    {
        return new FiatAmount(Math::add($this->amount, $this->extractValue($operand)), $this->currency);
    }

    /** @throws FiatAmountException */
    public function minus(FiatAmount | Quantity | string $operand): FiatAmount
    {
        return new FiatAmount(Math::sub($this->amount, $this->extractValue($operand)), $this->currency);
    }

    /** @throws FiatAmountException */
    public function multipliedBy(FiatAmount | Quantity | string $multiplier): FiatAmount
    {
        return new FiatAmount(Math::mul($this->amount, $this->extractValue($multiplier)), $this->currency);
    }

    /** @throws FiatAmountException */
    public function dividedBy(FiatAmount | Quantity | string $divisor): FiatAmount
    {
        return new FiatAmount(Math::div($this->amount, $this->extractValue($divisor)), $this->currency);
    }

    /** @throws FiatAmountException */
    private function extractValue(FiatAmount | Quantity | string $term): string
    {
        if (is_string($term)) {
            return $term;
        }

        if ($term instanceof Quantity) {
            return $term->quantity;
        }

        $this->assertCurrenciesMatch($this, $term);

        return $term->amount;
    }

    /** @throws FiatAmountException */
    private function assertCurrenciesMatch(FiatAmount ...$fiatAmounts): void
    {
        $currencies = array_unique(array_map(fn (FiatAmount $fiatAmount) => $fiatAmount->currency->name, $fiatAmounts));

        if (count($currencies) > 1) {
            throw FiatAmountException::fiatCurrenciesDoNotMatch(...$currencies);
        }
    }

    public function __toString(): string
    {
        return sprintf('%s%s', $this->currency->symbol(), $this->amount);
    }
}
