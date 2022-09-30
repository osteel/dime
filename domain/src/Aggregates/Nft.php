<?php

namespace Domain\Aggregates;

use Domain\Actions\AcquireNft;
use Domain\Actions\AverageNftCostBasis;
use Domain\Aggregates\Exceptions\NftException;
use Domain\Events\NftAcquired;
use Domain\Events\NftCostBasisAveraged;
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

    /** @throws NftException */
    public function averageCostBasis(AverageNftCostBasis $action): void
    {
        throw_unless($this->isAcquired, NftException::cannotAverageCostBasisBeforeAcquisition($this->aggregateRootId()));

        $this->recordThat(new NftCostBasisAveraged($this->aggregateRootId(), $action->averagingCostBasis));
    }

    public function applyNftCostBasisAveraged(NftCostBasisAveraged $event): void
    {
    }
}
