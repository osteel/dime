<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

final readonly class AssetSymbol
{
    public string $value;

    public function __construct(string $symbol, bool $isNft = false)
    {
        $this->value = $isNft ? $symbol : trim(strtoupper($symbol));
    }
}
