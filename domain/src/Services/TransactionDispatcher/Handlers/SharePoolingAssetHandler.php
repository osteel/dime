<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetRepository;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
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

    public function __construct(private readonly SharePoolingAssetRepository $sharePoolingRepository)
    {
    }

    /** @throws SharePoolingAssetHandlerException */
    public function handle(Acquisition | Disposal | Swap $transaction): void
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

    private function handleDisposal(Acquisition | Disposal | Swap $transaction, Asset $asset, Quantity $quantity): void
    {
        $sharePoolingId = SharePoolingAssetId::fromAsset($asset);
        $sharePooling = $this->sharePoolingRepository->get($sharePoolingId);

        $sharePooling->disposeOf(new DisposeOfSharePoolingAsset(
            date: $transaction->date,
            quantity: $quantity,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)),
        ));

        $this->sharePoolingRepository->save($sharePooling);
    }

    private function handleAcquisition(Acquisition | Disposal | Swap $transaction, Asset $asset, Quantity $quantity): void
    {
        $sharePoolingId = SharePoolingAssetId::fromAsset($asset);
        $sharePooling = $this->sharePoolingRepository->get($sharePoolingId);

        $sharePooling->acquire(new AcquireSharePoolingAsset(
            date: $transaction->date,
            quantity: $quantity,
            costBasis: $transaction->marketValue->plus($this->splitFees($transaction)),
        ));

        $this->sharePoolingRepository->save($sharePooling);
    }
}
