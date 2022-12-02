<?php

namespace Domain\Tests\Aggregates\Nft;

use Domain\Aggregates\Nft\Actions\AcquireNft;
use Domain\Aggregates\Nft\Actions\DisposeOfNft;
use Domain\Aggregates\Nft\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Nft\Nft;
use Domain\Aggregates\Nft\NftId;
use Domain\Tests\AggregateRootTestCase;
use EventSauce\EventSourcing\AggregateRootId;

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
        $nft = $this->repository->retrieve($this->aggregateRootId);

        match ($action::class) {
            AcquireNft::class => $nft->acquire($action),
            IncreaseNftCostBasis::class => $nft->increaseCostBasis($action),
            DisposeOfNft::class => $nft->disposeOf($action),
        };

        $this->repository->persist($nft);
    }
}
