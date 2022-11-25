<?php

namespace Domain\Tests\Nft;

use Domain\Nft\Actions\AcquireNft;
use Domain\Nft\Actions\DisposeOfNft;
use Domain\Nft\Actions\IncreaseNftCostBasis;
use Domain\Nft\Nft;
use Domain\Nft\NftId;
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
        $nft = $this->repository->retrieve($action->nftId);

        match ($action::class) {
            AcquireNft::class => $nft->acquire($action),
            IncreaseNftCostBasis::class => $nft->increaseCostBasis($action),
            DisposeOfNft::class => $nft->disposeOf($action),
        };

        $this->repository->persist($nft);
    }
}
