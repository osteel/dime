<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Domain\Services\Math\Math;
use Domain\ValueObjects\Exceptions\QuantityException;
use Stringable;

final readonly class Quantity implements Stringable
{
    /** @throws QuantityException */
    public function __construct(public string $quantity)
    {
        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $quantity) !== 1) {
            throw QuantityException::invalidQuantity($quantity);
        }
    }

    public static function zero(): Quantity
    {
        return new self('0');
    }

    public static function maximum(Quantity $quantity1, Quantity $quantity2): Quantity
    {
        return $quantity1->isGreaterThan($quantity2) ? $quantity1 : $quantity2;
    }

    public static function minimum(Quantity $quantity1, Quantity $quantity2): Quantity
    {
        return $quantity1->isLessThan($quantity2) ? $quantity1 : $quantity2;
    }

    public function copy(): Quantity
    {
        return new Quantity($this->quantity);
    }

    public function opposite(): Quantity
    {
        $newQuantity = str_starts_with($this->quantity, '-') ? ltrim($this->quantity, '-') : '-' . $this->quantity;

        return new Quantity($newQuantity);
    }

    public function isPositive(): bool
    {
        return Math::gte($this->quantity, '0');
    }

    public function isNegative(): bool
    {
        return Math::lt($this->quantity, '0');
    }

    public function isZero(): bool
    {
        return Math::eq($this->quantity, '0');
    }

    /** @throws QuantityException */
    public function isEqualTo(Quantity | string $quantity): bool
    {
        return Math::eq($this->quantity, $this->toQuantity($quantity)->quantity);
    }

    /** @throws QuantityException */
    public function isGreaterThan(Quantity | string $quantity): bool
    {
        return Math::gt($this->quantity, $this->toQuantity($quantity)->quantity);
    }

    /** @throws QuantityException */
    public function isGreaterThanOrEqualTo(Quantity | string $quantity): bool
    {
        return Math::gte($this->quantity, $this->toQuantity($quantity)->quantity);
    }

    /** @throws QuantityException */
    public function isLessThan(Quantity | string $quantity): bool
    {
        return Math::lt($this->quantity, $this->toQuantity($quantity)->quantity);
    }

    /** @throws QuantityException */
    public function isLessThanOrEqualTo(Quantity | string $quantity): bool
    {
        return Math::lte($this->quantity, $this->toQuantity($quantity)->quantity);
    }

    /** @throws QuantityException */
    public function plus(Quantity | string $quantity): Quantity
    {
        return new Quantity(Math::add($this->quantity, $this->toQuantity($quantity)->quantity));
    }

    /** @throws QuantityException */
    public function minus(Quantity | string $quantity): Quantity
    {
        return new Quantity(Math::sub($this->quantity, $this->toQuantity($quantity)->quantity));
    }

    /** @throws QuantityException */
    public function multipliedBy(Quantity | string $quantity): Quantity
    {
        return new Quantity(Math::mul($this->quantity, $this->toQuantity($quantity)->quantity));
    }

    /** @throws QuantityException */
    public function dividedBy(Quantity | string $quantity): Quantity
    {
        return new Quantity(Math::div($this->quantity, $this->toQuantity($quantity)->quantity));
    }

    /** @throws QuantityException */
    private function toQuantity(Quantity | string $quantity): Quantity
    {
        return $quantity instanceof Quantity ? $quantity : new self($quantity);
    }

    public function __toString(): string
    {
        return $this->quantity;
    }
}
