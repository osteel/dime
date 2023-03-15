<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Domain\Services\Math\Math;
use Stringable;

final readonly class Quantity implements Stringable
{
    public function __construct(public string $quantity)
    {
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

    public function isZero(): bool
    {
        return Math::eq($this->quantity, '0');
    }

    public function isEqualTo(Quantity | string $quantity): bool
    {
        return Math::eq($this->quantity, $this->extractValue($quantity));
    }

    public function isGreaterThan(Quantity | string $quantity): bool
    {
        return Math::gt($this->quantity, $this->extractValue($quantity));
    }

    public function isGreaterThanOrEqualTo(Quantity | string $quantity): bool
    {
        return Math::gte($this->quantity, $this->extractValue($quantity));
    }

    public function isLessThan(Quantity | string $quantity): bool
    {
        return Math::lt($this->quantity, $this->extractValue($quantity));
    }

    public function isLessThanOrEqualTo(Quantity | string $quantity): bool
    {
        return Math::lte($this->quantity, $this->extractValue($quantity));
    }

    public function plus(Quantity | string $quantity): Quantity
    {
        return new Quantity(Math::add($this->quantity, $this->extractValue($quantity)));
    }

    public function minus(Quantity | string $quantity): Quantity
    {
        return new Quantity(Math::sub($this->quantity, $this->extractValue($quantity)));
    }

    public function multipliedBy(Quantity | string $quantity): Quantity
    {
        return new Quantity(Math::mul($this->quantity, $this->extractValue($quantity)));
    }

    public function dividedBy(Quantity | string $quantity): Quantity
    {
        return new Quantity(Math::div($this->quantity, $this->extractValue($quantity)));
    }

    private function extractValue(Quantity | string $quantity): string
    {
        return $quantity instanceof Quantity ? $quantity->quantity : $quantity;
    }

    public function __toString(): string
    {
        return $this->quantity;
    }
}
