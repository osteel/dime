<?php

namespace Domain\Aggregates;

use Domain\Actions\AcquireNft;
use Domain\Actions\DisposeOfNft;
use Domain\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Exceptions\NftException;
use Domain\Events\NftAcquired;
use Domain\Events\NftCostBasisIncreased;
use Domain\Events\NftDisposedOf;
use Domain\Services\Math;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

final class Nft implements AggregateRoot
{
    use AggregateRootBehaviour;

    private ?FiatAmount $costBasis = null;

    /** @throws NftException */
    public function acquire(AcquireNft $action): void
    {
        if (! is_null($this->costBasis)) {
            throw NftException::alreadyAcquired($this->aggregateRootId);
        }

        $this->recordThat(new NftAcquired(nftId: $this->aggregateRootId, costBasis: $action->costBasis));
    }

    public function applyNftAcquired(NftAcquired $event): void
    {
        $this->costBasis = $event->costBasis;
    }

    /** @throws NftException */
    public function increaseCostBasis(IncreaseNftCostBasis $action): void
    {
        if (is_null($this->costBasis)) {
            throw NftException::cannotIncreaseCostBasisBeforeAcquisition($this->aggregateRootId);
        }

        if ($this->costBasis->currency !== $action->extraCostBasis->currency) {
            throw NftException::cannotIncreaseCostBasisFromDifferentCurrency(
                nftId: $this->aggregateRootId,
                from: $this->costBasis->currency,
                to: $action->extraCostBasis->currency,
            );
        }

        $newCostBasis = new FiatAmount(
            amount: Math::add($this->costBasis->amount, $action->extraCostBasis->amount),
            currency: $this->costBasis->currency,
        );

        $this->recordThat(new NftCostBasisIncreased(
            nftId: $this->aggregateRootId,
            previousCostBasis: $this->costBasis,
            extraCostBasis: $action->extraCostBasis,
            newCostBasis: $newCostBasis,
        ));
    }

    public function applyNftCostBasisIncreased(NftCostBasisIncreased $event): void
    {
        $this->costBasis = $event->newCostBasis;
    }

    /** @throws NftException */
    public function disposeOf(DisposeOfNft $action): void
    {
        if (is_null($this->costBasis)) {
            throw NftException::cannotDisposeOfBeforeAcquisition($this->aggregateRootId);
        }

        $this->recordThat(new NftDisposedOf(
            nftId: $this->aggregateRootId,
            costBasis: $this->costBasis,
            disposalProceeds: $action->disposalProceeds
        ));
    }

    public function applyNftDisposedOf(NftDisposedOf $event): void
    {
        $this->costBasis = null;
    }
}
