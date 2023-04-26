<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NonFungibleAssetHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\Traits\AttributesFees;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;
use Illuminate\Contracts\Bus\Dispatcher;

class NonFungibleAssetHandler
{
    use AttributesFees;

    public function __construct(private readonly Dispatcher $dispatcher)
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
        $this->dispatcher->dispatchSync(new DisposeOfNonFungibleAsset(
            asset: $asset,
            date: $transaction->date,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)),
        ));
    }

    private function handleAcquisition(Acquisition | Disposal | Swap $transaction, Asset $asset): void
    {
        $this->dispatcher->dispatchSync(new AcquireNonFungibleAsset(
            asset: $asset,
            date: $transaction->date,
            costBasis: $transaction->marketValue->plus($this->splitFees($transaction)),
        ));
    }
}
