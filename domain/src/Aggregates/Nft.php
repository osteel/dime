<?php

namespace Domain\Aggregates;

use Domain\Actions\AcquireNft;
use Domain\Aggregates\Exceptions\NftException;
use Domain\Events\NftAcquired;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

class Nft implements AggregateRoot
{
    use AggregateRootBehaviour;

    private bool $isAcquired = false;

    /** @throws NftException */
    public function acquire(AcquireNft $action): void
    {
        throw_if($this->isAcquired, NftException::alreadyAcquired($this->aggregateRootId()));

        $this->recordThat(new NftAcquired($this->aggregateRootId(), $action->costBasis));
    }

    public function applyNftAcquired(NftAcquired $event): void
    {
        $this->isAcquired = true;
    }
}
