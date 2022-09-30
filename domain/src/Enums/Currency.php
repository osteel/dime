<?php

namespace Domain\Enums;

enum Currency: string
{
    case GBP = 'gbp';

    public function symbol(): string
    {
        return match ($this) {
            Currency::GBP => '£',
        };
    }
}
