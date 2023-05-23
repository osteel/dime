<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Exceptions\AssetException;
use Stringable;

final readonly class Asset implements Stringable
{
    public string|FiatCurrency $symbol;

    /** @throws AssetException */
    public function __construct(string $symbol, public bool $isNonFungible = false)
    {
        $symbol = trim($isNonFungible ? $symbol : strtoupper($symbol));

        $this->symbol = FiatCurrency::tryFrom($symbol) ?? $symbol;

        if ($this->symbol instanceof FiatCurrency && $this->isNonFungible) {
            throw AssetException::fiatCurrencyIsAlwaysFungible($this->symbol);
        }
    }

    public static function nonFungible(string $symbol): self
    {
        return new self(symbol: $symbol, isNonFungible: true);
    }

    public function is(Asset $asset): bool
    {
        return $asset->symbol === $this->symbol && $asset->isNonFungible === $this->isNonFungible;
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
