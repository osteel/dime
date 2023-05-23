<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Exceptions\FiatAmountException;
use Domain\ValueObjects\Exceptions\QuantityException;
use Stringable;

final readonly class FiatAmount implements Stringable
{
    public Quantity $quantity;

    /** @throws QuantityException */
    public function __construct(
        Quantity|string $quantity,
        public FiatCurrency $currency,
    ) {
        $this->quantity = $quantity instanceof Quantity ? $quantity : new Quantity($quantity);
    }

    public static function GBP(Quantity|string $quantity): self
    {
        return new self($quantity, FiatCurrency::GBP);
    }

    public function zero(): FiatAmount
    {
        return new self(Quantity::zero(), $this->currency);
    }

    public function opposite(): FiatAmount
    {
        return new self($this->quantity->opposite(), $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->quantity->isPositive();
    }

    public function isNegative(): bool
    {
        return $this->quantity->isNegative();
    }

    public function isZero(): bool
    {
        return $this->quantity->isZero();
    }

    public function isEqualTo(FiatAmount|Quantity|string $amount): bool
    {
        return $this->quantity->isEqualTo($this->toQuantity($amount));
    }

    public function isGreaterThan(FiatAmount|Quantity|string $amount): bool
    {
        return $this->quantity->isGreaterThan($this->toQuantity($amount));
    }

    public function isGreaterThanOrEqualTo(FiatAmount|Quantity|string $amount): bool
    {
        return $this->quantity->isGreaterThanOrEqualTo($this->toQuantity($amount));
    }

    public function isLessThan(FiatAmount|Quantity|string $amount): bool
    {
        return $this->quantity->isLessThan($this->toQuantity($amount));
    }

    public function isLessThanOrEqualTo(FiatAmount|Quantity|string $amount): bool
    {
        return $this->quantity->isLessThanOrEqualTo($this->toQuantity($amount));
    }

    /** @throws FiatAmountException */
    public function plus(FiatAmount|Quantity|string $operand): FiatAmount
    {
        return new FiatAmount($this->quantity->plus($this->toQuantity($operand)), $this->currency);
    }

    /** @throws FiatAmountException */
    public function minus(FiatAmount|Quantity|string $operand): FiatAmount
    {
        return new FiatAmount($this->quantity->minus($this->toQuantity($operand)), $this->currency);
    }

    /** @throws FiatAmountException */
    public function multipliedBy(FiatAmount|Quantity|string $multiplier): FiatAmount
    {
        return new FiatAmount($this->quantity->multipliedBy($this->toQuantity($multiplier)), $this->currency);
    }

    /** @throws FiatAmountException */
    public function dividedBy(FiatAmount|Quantity|string $divisor): FiatAmount
    {
        return new FiatAmount($this->quantity->dividedBy($this->toQuantity($divisor)), $this->currency);
    }

    /**
     * @throws FiatAmountException
     * @throws QuantityException
     */
    private function toQuantity(FiatAmount|Quantity|string $term): Quantity
    {
        if ($term instanceof Quantity) {
            return $term;
        }

        if (is_string($term)) {
            return new Quantity($term);
        }

        $this->assertCurrenciesMatch($this, $term);

        return $term->quantity;
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
        return sprintf('%s%s', $this->currency->symbol(), (string) $this->quantity);
    }
}
