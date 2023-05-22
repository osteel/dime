<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects;

use Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions\SharePoolingAssetIdException;
use Domain\ValueObjects\AggregateRootId;
use Domain\ValueObjects\Asset;

final readonly class SharePoolingAssetId extends AggregateRootId
{
    public static function fromAsset(Asset $asset): static
    {
        ! $asset->isNonFungible || throw SharePoolingAssetIdException::assetIsNonFungible($asset);

        return self::fromString((string) $asset);
    }

    public function toAsset(): Asset
    {
        return new Asset($this->id);
    }
}
