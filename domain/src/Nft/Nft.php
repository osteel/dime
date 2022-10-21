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
            throw NftException::alreadyAcquired($action->nftId);
        }

        $this->recordThat(new NftAcquired(nftId: $action->nftId, costBasis: $action->costBasis));
    }

    public function applyNftAcquired(NftAcquired $event): void
    {
        $this->costBasis = $event->costBasis;
    }

    /** @throws NftException */
    public function increaseCostBasis(IncreaseNftCostBasis $action): void
    {
        if (is_null($this->costBasis)) {
            throw NftException::cannotIncreaseCostBasisBeforeAcquisition($action->nftId);
        }

        if ($this->costBasis->currency !== $action->costBasisIncrease->currency) {
            throw NftException::cannotIncreaseCostBasisFromDifferentFiatCurrency(
                nftId: $action->nftId,
                from: $this->costBasis->currency,
                to: $action->costBasisIncrease->currency,
            );
        }

        $this->recordThat(new NftCostBasisIncreased(
            nftId: $action->nftId,
            costBasisIncrease: $action->costBasisIncrease,
        ));
    }

    public function applyNftCostBasisIncreased(NftCostBasisIncreased $event): void
    {
        assert(! is_null($this->costBasis));

        $newCostBasis = new FiatAmount(
            amount: Math::add($this->costBasis->amount, $event->costBasisIncrease->amount),
            currency: $this->costBasis->currency,
        );

        $this->costBasis = $newCostBasis;
    }

    /** @throws NftException */
    public function disposeOf(DisposeOfNft $action): void
    {
        if (is_null($this->costBasis)) {
            throw NftException::cannotDisposeOfBeforeAcquisition($action->nftId);
        }

        $this->recordThat(new NftDisposedOf(
            nftId: $action->nftId,
            costBasis: $this->costBasis,
            disposalProceeds: $action->disposalProceeds
        ));
    }

    public function applyNftDisposedOf(NftDisposedOf $event): void
    {
        $this->costBasis = null;
    }
}
