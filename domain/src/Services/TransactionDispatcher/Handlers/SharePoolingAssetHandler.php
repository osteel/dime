<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingAssetHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\Traits\AttributesFees;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

class SharePoolingAssetHandler
{
    use AttributesFees;

    public function __construct(private readonly ActionRunner $runner)
    {
    }

    /** @throws SharePoolingAssetHandlerException */
    public function handle(Acquisition|Disposal|Swap $transaction): void
    {
        $transaction->hasSharePoolingAsset() || throw SharePoolingAssetHandlerException::noSharePoolingAsset($transaction);

        if ($transaction instanceof Acquisition) {
            $this->handleAcquisition($transaction, $transaction->asset, $transaction->quantity);

            return;
        }

        if ($transaction instanceof Disposal) {
            $this->handleDisposal($transaction, $transaction->asset, $transaction->quantity);

            return;
        }

        if ($transaction->acquiredAssetIsSharePoolingAsset()) {
            $this->handleAcquisition($transaction, $transaction->acquiredAsset, $transaction->acquiredQuantity);
        }

        if ($transaction->disposedOfAssetIsSharePoolingAsset()) {
            $this->handleDisposal($transaction, $transaction->disposedOfAsset, $transaction->disposedOfQuantity);
        }
    }

    private function handleDisposal(Disposal|Swap $transaction, Asset $asset, Quantity $quantity): void
    {
        $this->runner->run(new DisposeOfSharePoolingAsset(
            asset: $asset,
            date: $transaction->date,
            quantity: $quantity,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)),
            forFiat: $transaction instanceof Swap && $transaction->acquiredAsset->isFiat(),
        ));
    }

    private function handleAcquisition(Acquisition|Swap $transaction, Asset $asset, Quantity $quantity): void
    {
        $this->runner->run(new AcquireSharePoolingAsset(
            asset: $asset,
            date: $transaction->date,
            quantity: $quantity,
            costBasis: $transaction->marketValue->plus($this->splitFees($transaction)),
            forFiat: $transaction instanceof Swap && $transaction->disposedOfAsset->isFiat(),
        ));
    }
}
