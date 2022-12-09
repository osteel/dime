<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher;

use Domain\Enums\Operation;
use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\Services\TransactionDispatcher\Handlers\NftHandler;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingHandler;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\ValueObjects\Transaction;

final class TransactionDispatcher
{
    public function __construct(
        private readonly IncomeHandler $incomeHandler,
        private readonly TransferHandler $transferHandler,
        private readonly NftHandler $nftHandler,
        private readonly SharePoolingHandler $sharePoolingHandler,
    ) {
    }

    public function dispatch(Transaction $transaction): void
    {
        $this->handleIncome($transaction)
            ->handleTransfer($transaction)
            ->handleNft($transaction)
            ->handleSharePooling($transaction)
            ->handleNetworkFee($transaction)
            ->handlePlatformFee($transaction);
    }

    private function handleIncome(Transaction $transaction): self
    {
        if (! $transaction->isIncome) {
            return $this;
        }

        $this->incomeHandler->handle($transaction);

        return $this;
    }

    private function handleTransfer(Transaction $transaction): self
    {
        if (! $transaction->isTransfer()) {
            return $this;
        }

        $this->transferHandler->handle($transaction);

        return $this;
    }

    private function handleNft(Transaction $transaction): self
    {
        if (! $transaction->involvesNfts() || $transaction->isTransfer()) {
            return $this;
        }

        $this->nftHandler->handle($transaction);

        return $this;
    }

    private function handleSharePooling(Transaction $transaction): self
    {
        if (! $transaction->involvesSharePooling() || $transaction->isTransfer()) {
            return $this;
        }

        $this->sharePoolingHandler->handle($transaction);

        return $this;
    }

    private function handleNetworkFee(Transaction $transaction): self
    {
        if (is_null($transaction->networkFeeMarketValue) || $transaction->networkFeeIsFiat()) {
            return $this;
        }

        $this->sharePoolingHandler->handle(new Transaction(
            date: $transaction->date,
            operation: Operation::Send,
            marketValue: $transaction->networkFeeMarketValue,
            sentAsset: $transaction->networkFeeCurrency,
            sentQuantity: $transaction->networkFeeQuantity,
        ));

        return $this;
    }

    private function handlePlatformFee(Transaction $transaction): self
    {
        if (is_null($transaction->platformFeeMarketValue) || $transaction->platformFeeIsFiat()) {
            return $this;
        }

        $this->sharePoolingHandler->handle(new Transaction(
            date: $transaction->date,
            operation: Operation::Send,
            marketValue: $transaction->platformFeeMarketValue,
            sentAsset: $transaction->platformFeeCurrency,
            sentQuantity: $transaction->platformFeeQuantity,
        ));

        return $this;
    }
}
