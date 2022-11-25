<?php

declare(strict_types=1);

namespace Domain\Enums;

enum FiatCurrency: string
{
    case GBP = 'gbp';
    case EUR = 'eur';

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
