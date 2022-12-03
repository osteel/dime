<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling;

use Domain\AggregateRootId;
use Domain\Aggregates\SharePooling\Services\AssetSymbolNormaliser\AssetSymbolNormaliser;
use Ramsey\Uuid\Uuid;

final class SharePoolingId extends AggregateRootId
{
    private const NAMESPACE = '94ff3977-46f5-4efe-8a89-e474d375232f';

    public static function fromSymbol(string $symbol): static
    {
        // @phpstan-ignore-next-line
        return self::fromString(Uuid::uuid5(self::NAMESPACE, AssetSymbolNormaliser::normalise($symbol))->toString());
    }
}
