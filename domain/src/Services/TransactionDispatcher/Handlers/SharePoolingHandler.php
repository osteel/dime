<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\Repositories\SharePoolingRepository;
use Domain\Aggregates\SharePooling\SharePoolingId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\Traits\AttributesFees;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

class SharePoolingHandler
{
    use AttributesFees;

    public function __construct(private readonly SharePoolingRepository $sharePoolingRepository)
    {
    }

    /** @throws SharePoolingHandlerException */
    public function handle(Acquisition | Disposal | Swap $transaction): void
    {
        $transaction->hasSharePoolingAsset() || throw SharePoolingHandlerException::noSharePoolingAsset($transaction);

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
        $sharePoolingId = SharePoolingId::fromAsset($asset);
        $sharePooling = $this->sharePoolingRepository->get($sharePoolingId);

        $sharePooling->disposeOf(new DisposeOfSharePoolingToken(
            date: $transaction->date,
            quantity: $quantity,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)),
        ));

        $this->sharePoolingRepository->save($sharePooling);
    }

    private function handleAcquisition(Acquisition | Disposal | Swap $transaction, Asset $asset, Quantity $quantity): void
    {
        $sharePoolingId = SharePoolingId::fromAsset($asset);
        $sharePooling = $this->sharePoolingRepository->get($sharePoolingId);

        $sharePooling->acquire(new AcquireSharePoolingToken(
            date: $transaction->date,
            quantity: $quantity,
            costBasis: $transaction->marketValue->plus($this->splitFees($transaction)),
        ));

        $this->sharePoolingRepository->save($sharePooling);
    }
}
