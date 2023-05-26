<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset;

use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use EventSauce\EventSourcing\AggregateRoot;

/** @extends AggregateRoot<NonFungibleAssetId> */
interface NonFungibleAssetContract extends AggregateRoot
{
    public function isAlreadyAcquired(): bool;

    public function acquire(AcquireNonFungibleAsset $action): void;

    public function increaseCostBasis(IncreaseNonFungibleAssetCostBasis $action): void;

    public function disposeOf(DisposeOfNonFungibleAsset $action): void;
}
