<?php

namespace Domain\Aggregates;

use Domain\Actions\AcquireNft;
use Domain\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Exceptions\NftException;
use Domain\Events\NftAcquired;
use Domain\Events\NftCostBasisIncreased;
use Domain\Services\Math;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

class Nft implements AggregateRoot
{
    use AggregateRootBehaviour;

    private bool $isAcquired = false;
    private FiatAmount $costBasis = null;

    /** @throws NftException */
    public function acquire(AcquireNft $action): void
    {
        throw_if($this->isAcquired, NftException::alreadyAcquired($this->aggregateRootId()));

        $this->recordThat(new NftAcquired(nftId: $this->aggregateRootId(), costBasis: $action->costBasis));
    }

    public function applyNftAcquired(NftAcquired $event): void
    {
        $this->isAcquired = true;
        $this->costBasis = $event->costBasis;
    }

    /** @throws NftException */
    public function increaseCostBasis(IncreaseNftCostBasis $action): void
    {
        throw_unless($this->isAcquired, NftException::cannotIncreaseCostBasisBeforeAcquisition($this->aggregateRootId()));

        $this->recordThat(new NftCostBasisIncreased(
            nftId: $this->aggregateRootId(),
            previousCostBasis: $this->costBasis,
            extraCostBasis: $action->extraCostBasis,
            newCostbasis: ,
        ));
    }

    public function applyNftCostBasisIncreased(NftCostBasisIncreased $event): void
    {
    }
}
