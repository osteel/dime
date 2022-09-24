<?php

namespace Domain\Aggregates;

use Domain\Actions\AcquireNft;
use Domain\Events\NftAcquired;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

class Nft implements AggregateRoot
{
    use AggregateRootBehaviour;

    public static function acquire(AcquireNft $action): Nft
    {
        $nft = new static($action->nftId);
        $nft->recordThat(new NftAcquired($nft->aggregateRootId(), $action->costBasis));

        return $nft;
    }

    public function applyNftAcquired(NftAcquired $event): void
    {
    }
}
