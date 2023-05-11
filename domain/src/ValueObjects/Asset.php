<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Exceptions\AssetException;
use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Stringable;

final readonly class Asset implements SerializablePayload, Stringable
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

    /** @return array{symbol:string,is_non_fungible:string} */
    public function toPayload(): array
    {
        return [
            'symbol' => $this->symbol instanceof FiatCurrency ? $this->symbol->value : $this->symbol,
            'is_non_fungible' => (string) $this->isNonFungible,
        ];
    }

    /** @param array{symbol:string,is_non_fungible:string} $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            symbol: $payload['symbol'],
            isNonFungible: (bool) $payload['is_non_fungible'],
        );
    }

    public function __toString(): string
    {
        return $this->symbol instanceof FiatCurrency ? $this->symbol->value : $this->symbol;
    }
}
