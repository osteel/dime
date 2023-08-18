<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NonFungibleAssetHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\Traits\AttributesFees;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

class NonFungibleAssetHandler
{
    use AttributesFees;

    public function __construct(private readonly ActionRunner $runner)
    {
    }

    /** @throws NonFungibleAssetHandlerException */
    public function handle(Acquisition|Disposal|Swap $transaction): void
    {
        $transaction->hasNonFungibleAsset() || throw NonFungibleAssetHandlerException::noNonFungibleAsset($transaction);

        if ($transaction instanceof Acquisition && $transaction->asset->isNonFungible) {
            $this->handleAcquisition($transaction, $transaction->asset);

            return;
        }

        if ($transaction instanceof Disposal && $transaction->asset->isNonFungible) {
            $this->handleDisposal($transaction, $transaction->asset);

            return;
        }

        assert($transaction instanceof Swap);

        if ($transaction->acquiredAsset->isNonFungible) {
            $this->handleAcquisition($transaction, $transaction->acquiredAsset);
        }

        if ($transaction->disposedOfAsset->isNonFungible) {
            $this->handleDisposal($transaction, $transaction->disposedOfAsset);
        }
    }

    private function handleDisposal(Disposal|Swap $transaction, Asset $asset): void
    {
        $this->runner->run(new DisposeOfNonFungibleAsset(
            asset: $asset,
            date: $transaction->date,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)),
            forFiat: $transaction instanceof Swap && $transaction->acquiredAsset->isFiat(),
        ));
    }

    private function handleAcquisition(Acquisition|Swap $transaction, Asset $asset): void
    {
        $this->runner->run(new AcquireNonFungibleAsset(
            asset: $asset,
            date: $transaction->date,
            costBasis: $transaction->marketValue->plus($this->splitFees($transaction)),
            forFiat: $transaction instanceof Swap && $transaction->disposedOfAsset->isFiat(),
        ));
    }
}
