<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Domain\Enums\FiatCurrency;
use Stringable;

final readonly class Asset implements Stringable
{
    public string|FiatCurrency $symbol;

    public function __construct(string $symbol, public bool $isNft = false)
    {
        $symbol = trim($isNft ? $symbol : strtoupper($symbol));

        $this->symbol = FiatCurrency::tryFrom($symbol) ?? $symbol;
    }

    public function isFiat(): bool
    {
        return $this->symbol instanceof FiatCurrency;
    }

    public function __toString(): string
    {
        return $this->symbol instanceof FiatCurrency ? $this->symbol->value : $this->symbol;
    }
}
