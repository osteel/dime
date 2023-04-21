<?php

namespace Domain\Tests\Aggregates\SharePoolingAsset;

use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use Domain\Tests\AggregateRootTestCase;
use EventSauce\EventSourcing\AggregateRootId;

abstract class SharePoolingAssetTestCase extends AggregateRootTestCase
{
    protected function newAggregateRootId(): AggregateRootId
    {
        return SharePoolingAssetId::generate();
    }

    protected function aggregateRootClassName(): string
    {
        return SharePoolingAsset::class;
    }

    public function handle(object $action)
    {
        $sharePoolingAsset = $this->repository->retrieve($this->aggregateRootId);

        match ($action::class) {
            AcquireSharePoolingAsset::class => $sharePoolingAsset->acquire($action),
            DisposeOfSharePoolingAsset::class => $sharePoolingAsset->disposeOf($action),
        };

        $this->repository->persist($sharePoolingAsset);
    }
}
