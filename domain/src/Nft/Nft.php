<?php

declare(strict_types=1);

namespace Domain\Nft;

use Domain\Nft\Actions\AcquireNft;
use Domain\Nft\Actions\DisposeOfNft;
use Domain\Nft\Actions\IncreaseNftCostBasis;
use Domain\Nft\Events\NftAcquired;
use Domain\Nft\Events\NftCostBasisIncreased;
use Domain\Nft\Events\NftDisposedOf;
use Domain\Nft\Exceptions\NftException;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

/** @property \Domain\Nft\NftId $aggregateRootId */
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

        $this->recordThat(new NftAcquired(
            date: $action->date,
            costBasis: $action->costBasis,
        ));
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

        $this->recordThat(new NftCostBasisIncreased(
            date: $action->date,
            costBasisIncrease: $action->costBasisIncrease,
        ));
    }

    public function applyNftCostBasisIncreased(NftCostBasisIncreased $event): void
    {
        assert(! is_null($this->costBasis));

        $this->costBasis = $this->costBasis->plus($event->costBasisIncrease);
    }

    /** @throws NftException */
    public function disposeOf(DisposeOfNft $action): void
    {
        if (is_null($this->costBasis)) {
            throw NftException::cannotDisposeOfBeforeAcquisition($this->aggregateRootId);
        }

        $this->recordThat(new NftDisposedOf(
            date: $action->date,
            costBasis: $this->costBasis,
            proceeds: $action->proceeds
        ));
    }

    public function applyNftDisposedOf(NftDisposedOf $event): void
    {
        $this->costBasis = null;
    }
}
