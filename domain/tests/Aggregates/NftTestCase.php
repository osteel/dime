<?php

namespace Domain\Tests\Aggregates;

use Domain\Actions\AcquireNft;
use Domain\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Nft;
use Domain\Aggregates\NftId;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

abstract class NftTestCase extends AggregateRootTestCase
{
    protected function newAggregateRootId(): AggregateRootId
    {
        return NftId::generate();
    }

    protected function aggregateRootClassName(): string
    {
        return Nft::class;
    }

    public function handle(object $action)
    {
        $nft = $this->repository->retrieve($action->nftId);

        match ($action::class) {
            AcquireNft::class => $nft->acquire($action),
            IncreaseNftCostBasis::class => $nft->increaseCostBasis($action),
        };

        $this->repository->persist($nft);
    }

    protected function messageDispatcher(): MessageDispatcher
    {
        return new SynchronousMessageDispatcher();
    }
}
