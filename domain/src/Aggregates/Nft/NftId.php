<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft;

use Domain\AggregateRootId;
use Ramsey\Uuid\Uuid;

final class NftId extends AggregateRootId
{
    private const NAMESPACE = '64d4faf2-d67a-4716-9b3c-bed1ca053068';

    public static function fromNftId(string $nftId): static
    {
        return self::fromString(Uuid::uuid5(self::NAMESPACE, $nftId)->toString());
    }
}
