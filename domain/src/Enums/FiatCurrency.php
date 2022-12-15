<?php

declare(strict_types=1);

namespace Domain\Enums;

enum FiatCurrency: string
{
    case GBP = 'GBP';
    case EUR = 'EUR';

    public function name(): string
    {
        return match ($this) {
            FiatCurrency::GBP => 'Pound sterling',
            FiatCurrency::EUR => 'Euro',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            FiatCurrency::GBP => '£',
            FiatCurrency::EUR => '€',
        };
    }
}
