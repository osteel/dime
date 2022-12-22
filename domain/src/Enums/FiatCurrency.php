<?php

declare(strict_types=1);

namespace Domain\Enums;

enum FiatCurrency: string
{
    case CAD = 'CAD';
    case GBP = 'GBP';
    case EUR = 'EUR';

    public function name(): string
    {
        return match ($this) {
            FiatCurrency::CAD => 'Canadian dollar',
            FiatCurrency::GBP => 'Pound sterling',
            FiatCurrency::EUR => 'Euro',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            FiatCurrency::CAD => 'C$',
            FiatCurrency::GBP => '£',
            FiatCurrency::EUR => '€',
        };
    }
}
