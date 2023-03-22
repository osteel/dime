<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\Repositories\SharePoolingRepository;
use Domain\Aggregates\SharePooling\SharePoolingId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\Traits\AttributesFees;
use Domain\ValueObjects\Transaction;

class SharePoolingHandler
{
    use AttributesFees;

    public function __construct(private readonly SharePoolingRepository $sharePoolingRepository)
    {
    }

    /** @throws SharePoolingHandlerException */
    public function handle(Transaction $transaction): void
    {
        $this->validate($transaction);

        if ($transaction->sentAssetFallsUnderSharePooling()) {
            $this->handleDisposal($transaction);
        }

        if ($transaction->receivedAssetFallsUnderSharePooling()) {
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

        $sharePoolingId = SharePoolingId::fromAssetSymbol($transaction->sentAsset);
        $sharePooling = $this->sharePoolingRepository->get($sharePoolingId);

        $sharePooling->disposeOf(new DisposeOfSharePoolingToken(
            date: $transaction->date,
            quantity: $transaction->sentQuantity,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)), // @phpstan-ignore-line
        ));

        $this->sharePoolingRepository->save($sharePooling);
    }

    private function handleAcquisition(Transaction $transaction): void
    {
        assert($transaction->receivedAsset !== null);

        $sharePoolingId = SharePoolingId::fromAssetSymbol($transaction->receivedAsset);
        $sharePooling = $this->sharePoolingRepository->get($sharePoolingId);

        $sharePooling->acquire(new AcquireSharePoolingToken(
            date: $transaction->date,
            quantity: $transaction->receivedQuantity,
            costBasis: $transaction->marketValue->plus($this->splitFees($transaction)), // @phpstan-ignore-line
        ));

        $this->sharePoolingRepository->save($sharePooling);
    }
}
