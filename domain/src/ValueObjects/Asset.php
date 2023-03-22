<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

final readonly class Asset
{
    public string $symbol;

    public function __construct(string $symbol, public bool $isNft = false)
    {
        $this->symbol = trim($isNft ? $symbol : strtoupper($symbol));
    }
}
