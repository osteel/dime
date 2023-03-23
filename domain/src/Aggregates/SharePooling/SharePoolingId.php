<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling;

use Domain\AggregateRootId;
use Domain\ValueObjects\Asset;
use Ramsey\Uuid\Uuid;

final class SharePoolingId extends AggregateRootId
{
    private const NAMESPACE = '94ff3977-46f5-4efe-8a89-e474d375232f';

    public static function fromAsset(Asset $asset): static
    {
        return self::fromString(Uuid::uuid5(self::NAMESPACE, (string) $asset)->toString());
    }
}
