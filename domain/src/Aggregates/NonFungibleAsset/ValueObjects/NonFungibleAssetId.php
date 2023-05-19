<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\ValueObjects;

use Domain\Aggregates\NonFungibleAsset\ValueObjects\Exceptions\NonFungibleAssetIdException;
use Domain\ValueObjects\AggregateRootId;
use Domain\ValueObjects\Asset;

final readonly class NonFungibleAssetId extends AggregateRootId
{
    /** @throws NonFungibleAssetIdException */
    public static function fromAsset(Asset $asset): static
    {
        $asset->isNonFungible || throw NonFungibleAssetIdException::assetIsFungible($asset);

        return self::fromString((string) $asset);
    }

    public function toAsset(): Asset
    {
        return Asset::nonFungible($this->id);
    }
}
