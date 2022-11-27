<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Domain\Enums\FiatCurrency;
use Domain\Services\Math;
use Domain\ValueObjects\Exceptions\FiatAmountException;
use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Stringable;

final class FiatAmount implements SerializablePayload, Stringable
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

    public function isEqualTo(FiatAmount | Quantity | string $amount): bool
    {
        return Math::eq($this->amount, $this->extractValue($amount));
    }

    public function isGreaterThan(FiatAmount | Quantity | string $amount): bool
    {
        return Math::gt($this->amount, $this->extractValue($amount));
    }

    public function isGreaterThanOrEqualTo(FiatAmount | Quantity | string $amount): bool
    {
        return Math::gte($this->amount, $this->extractValue($amount));
    }

    public function isLessThan(FiatAmount | Quantity | string $amount): bool
    {
        return Math::lt($this->amount, $this->extractValue($amount));
    }

    public function isLessThanOrEqualTo(FiatAmount | Quantity | string $amount): bool
    {
        return Math::lte($this->amount, $this->extractValue($amount));
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

    /** @return array<string, string> */
    public function toPayload(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->value,
        ];
    }

    /** @param array<string, string> $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            amount: $payload['amount'],
            currency: FiatCurrency::from($payload['currency']),
        );
    }

    public function __toString(): string
    {
        return sprintf('%s%s', $this->currency->symbol(), $this->amount);
    }
}
