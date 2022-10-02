<?php

namespace Domain\Enums;

enum Currency: string
{
    case GBP = 'gbp';
    case EUR = 'eur';

    public function name(): string
    {
        return match ($this) {
            Currency::GBP => 'Pound sterling',
            Currency::EUR => 'Euro',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            Currency::GBP => '£',
            Currency::EUR => '€',
        };
    }
}
