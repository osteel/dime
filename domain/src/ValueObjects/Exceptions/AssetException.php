<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Exceptions;

use Domain\Enums\FiatCurrency;
use RuntimeException;

final class AssetException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fiatCurrencyIsAlwaysFungible(FiatCurrency $currency): self
    {
        return new self(sprintf('Fiat currency %s cannot be non-fungible', $currency->name()));
    }
}
