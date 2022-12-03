<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Services\AssetSymbolNormaliser;

final class AssetSymbolNormaliser
{
    public static function normalise(?string $symbol): ?string
    {
        return is_null($symbol) ? null : trim(strtoupper($symbol));
    }
}
