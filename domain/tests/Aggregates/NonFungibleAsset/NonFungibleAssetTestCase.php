<?php

namespace Domain\Tests\Aggregates\NonFungibleAsset;

use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAssetId;
use Domain\Tests\AggregateRootTestCase;
use EventSauce\EventSourcing\AggregateRootId;

abstract class NonFungibleAssetTestCase extends AggregateRootTestCase
{
    protected function newAggregateRootId(): AggregateRootId
    {
        return NonFungibleAssetId::generate();
    }

    protected function aggregateRootClassName(): string
    {
        return NonFungibleAsset::class;
    }

    public function handle(object $action)
    {
        $nonFungibleAsset = $this->repository->retrieve($this->aggregateRootId);

        match ($action::class) {
            AcquireNonFungibleAsset::class => $nonFungibleAsset->acquire($action),
            IncreaseNonFungibleAssetCostBasis::class => $nonFungibleAsset->increaseCostBasis($action),
            DisposeOfNonFungibleAsset::class => $nonFungibleAsset->disposeOf($action),
        };

        $this->repository->persist($nonFungibleAsset);
    }
}
