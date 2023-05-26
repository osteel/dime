<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher;

use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\Services\TransactionDispatcher\Handlers\NonFungibleAssetHandler;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingAssetHandler;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;
use Domain\ValueObjects\Transactions\Transaction;
use Domain\ValueObjects\Transactions\Transfer;

final class TransactionDispatcher implements TransactionDispatcherContract
{
    public function __construct(
        private readonly IncomeHandler $incomeHandler,
        private readonly TransferHandler $transferHandler,
        private readonly NonFungibleAssetHandler $nonFungibleAssetHandler,
        private readonly SharePoolingAssetHandler $sharePoolingAssetHandler,
    ) {
    }

    public function dispatch(Transaction $transaction): void
    {
        $this->handleIncome($transaction)
            ->handleTransfer($transaction)
            ->handleNonFungibleAsset($transaction)
            ->handleSharePoolingAsset($transaction)
            ->handleFee($transaction);
    }

    private function handleIncome(Transaction $transaction): self
    {
        if ($transaction instanceof Acquisition && $transaction->isIncome) {
            $this->incomeHandler->handle($transaction);
        }

        return $this;
    }

    private function handleTransfer(Transaction $transaction): self
    {
        if ($transaction instanceof Transfer) {
            $this->transferHandler->handle($transaction);
        }

        return $this;
    }

    private function handleNonFungibleAsset(Transaction $transaction): self
    {
        if (! $transaction instanceof Acquisition && ! $transaction instanceof Disposal && ! $transaction instanceof Swap) {
            return $this;
        }

        if ($transaction->hasNonFungibleAsset()) {
            $this->nonFungibleAssetHandler->handle($transaction);
        }

        return $this;
    }

    private function handleSharePoolingAsset(Transaction $transaction): self
    {
        if (! $transaction instanceof Acquisition && ! $transaction instanceof Disposal && ! $transaction instanceof Swap) {
            return $this;
        }

        if ($transaction->hasSharePoolingAsset()) {
            $this->sharePoolingAssetHandler->handle($transaction);
        }

        return $this;
    }

    private function handleFee(Transaction $transaction): self
    {
        if (! $transaction->hasFee() || $transaction->feeIsFiat()) {
            return $this;
        }

        assert($transaction->fee?->currency instanceof Asset);

        $this->sharePoolingAssetHandler->handle(new Disposal(
            date: $transaction->date,
            asset: $transaction->fee->currency,
            quantity: $transaction->fee->quantity,
            marketValue: $transaction->fee->marketValue,
        ));

        return $this;
    }
}
