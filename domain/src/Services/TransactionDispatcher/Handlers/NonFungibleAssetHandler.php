<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAssetId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NonFungibleAssetHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\Traits\AttributesFees;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

class NonFungibleAssetHandler
{
    use AttributesFees;

    public function __construct(private readonly NonFungibleAssetRepository $nonFungibleAssetRepository)
    {
    }

    /** @throws NonFungibleAssetHandlerException */
    public function handle(Acquisition | Disposal | Swap $transaction): void
    {
        $transaction->hasNonFungibleAsset() || throw NonFungibleAssetHandlerException::noNonFungibleAsset($transaction);

        if ($transaction instanceof Acquisition && $transaction->asset->isNonFungibleAsset) {
            $this->handleAcquisition($transaction, $transaction->asset);

            return;
        }

        if ($transaction instanceof Disposal && $transaction->asset->isNonFungibleAsset) {
            $this->handleDisposal($transaction, $transaction->asset);

            return;
        }

        assert($transaction instanceof Swap);

        if ($transaction->acquiredAsset->isNonFungibleAsset) {
            $this->handleAcquisition($transaction, $transaction->acquiredAsset);
        }

        if ($transaction->disposedOfAsset->isNonFungibleAsset) {
            $this->handleDisposal($transaction, $transaction->disposedOfAsset);
        }
    }

    private function handleDisposal(Acquisition | Disposal | Swap $transaction, Asset $asset): void
    {
        $nonFungibleAssetId = NonFungibleAssetId::fromNonFungibleAssetId((string) $asset);
        $nonFungibleAsset = $this->nonFungibleAssetRepository->get($nonFungibleAssetId);

        $nonFungibleAsset->disposeOf(new DisposeOfNonFungibleAsset(
            date: $transaction->date,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)),
        ));

        $this->nonFungibleAssetRepository->save($nonFungibleAsset);
    }

    private function handleAcquisition(Acquisition | Disposal | Swap $transaction, Asset $asset): void
    {
        $nonFungibleAssetId = NonFungibleAssetId::fromNonFungibleAssetId((string) $asset);
        $nonFungibleAsset = $this->nonFungibleAssetRepository->get($nonFungibleAssetId);

        if ($nonFungibleAsset->isAlreadyAcquired()) {
            $nonFungibleAsset->increaseCostBasis(new IncreaseNonFungibleAssetCostBasis(
                date: $transaction->date,
                costBasisIncrease: $transaction->marketValue->plus($this->splitFees($transaction)),
            ));
        } else {
            $nonFungibleAsset->acquire(new AcquireNonFungibleAsset(
                date: $transaction->date,
                costBasis: $transaction->marketValue->plus($this->splitFees($transaction)),
            ));
        }

        $this->nonFungibleAssetRepository->save($nonFungibleAsset);
    }
}
