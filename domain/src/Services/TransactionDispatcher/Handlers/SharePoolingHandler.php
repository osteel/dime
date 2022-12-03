<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\Repositories\SharePoolingRepository;
use Domain\Aggregates\SharePooling\SharePoolingId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingHandlerException;
use Domain\ValueObjects\Transaction;

class SharePoolingHandler
{
    public function __construct(private SharePoolingRepository $sharePoolingRepository)
    {
    }

    /** @throws SharePoolingHandlerException */
    public function handle(Transaction $transaction): void
    {
        $this->validate($transaction);

        if (! is_null($transaction->sentAsset) && ! $transaction->sentAssetIsNft) {
            $this->handleDisposal($transaction);
        }

        if (! is_null($transaction->receivedAsset) && ! $transaction->receivedAssetIsNft) {
            $this->handleAcquisition($transaction);
        }
    }

    /** @throws SharePoolingHandlerException */
    private function validate(Transaction $transaction): void
    {
        $transaction->isReceive()
            || $transaction->isSend()
            || $transaction->isSwap()
            || throw SharePoolingHandlerException::unsupportedOperation($transaction);

        $transaction->receivedAssetIsNft
            && $transaction->sentAssetIsNft
            && throw SharePoolingHandlerException::bothNfts($transaction);
    }

    private function handleDisposal(Transaction $transaction): void
    {
        assert($transaction->sentAsset !== null);

        $sharePoolingId = SharePoolingId::fromSymbol($transaction->sentAsset);
        $sharePooling = $this->sharePoolingRepository->get($sharePoolingId);

        $sharePooling->disposeOf(new DisposeOfSharePoolingToken(
            date: $transaction->date,
            quantity: $transaction->sentQuantity,
            proceeds: $transaction->costBasis,
        ));
    }

    private function handleAcquisition(Transaction $transaction): void
    {
        assert($transaction->receivedAsset !== null);

        $sharePoolingId = SharePoolingId::fromSymbol($transaction->receivedAsset);
        $sharePooling = $this->sharePoolingRepository->get($sharePoolingId);

        $sharePooling->acquire(new AcquireSharePoolingToken(
            date: $transaction->date,
            quantity: $transaction->receivedQuantity,
            costBasis: $transaction->costBasis,
        ));
    }
}
