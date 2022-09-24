<?php

namespace Domain\Tests\Aggregates;

use Domain\Actions\AcquireNft;
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
        if ($action instanceof AcquireNft) {
            $nft = Nft::acquire($action);
        } else {
            $nft = $this->repository->retrieve($action->nftId);
        }

        $this->repository->persist($nft);
    }

    protected function messageDispatcher(): MessageDispatcher
    {
        return new SynchronousMessageDispatcher();
    }
}
