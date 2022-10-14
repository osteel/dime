<?php

namespace Domain\Nft;

use Domain\Nft\Actions\AcquireNft;
use Domain\Nft\Actions\DisposeOfNft;
use Domain\Nft\Actions\IncreaseNftCostBasis;
use Domain\Nft\Events\NftAcquired;
use Domain\Nft\Events\NftCostBasisIncreased;
use Domain\Nft\Events\NftDisposedOf;
use Domain\Nft\Exceptions\NftException;
use Domain\Services\Math\Math;
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

        if ($this->costBasis->currency !== $action->costBasisIncrease->currency) {
            throw NftException::cannotIncreaseCostBasisFromDifferentCurrency(
                nftId: $this->aggregateRootId,
                from: $this->costBasis->currency,
                to: $action->costBasisIncrease->currency,
            );
        }

        $newCostBasis = new FiatAmount(
            amount: Math::add($this->costBasis->amount, $action->costBasisIncrease->amount),
            currency: $this->costBasis->currency,
        );

        $this->recordThat(new NftCostBasisIncreased(
            nftId: $this->aggregateRootId,
            previousCostBasis: $this->costBasis,
            costBasisIncrease: $action->costBasisIncrease,
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
