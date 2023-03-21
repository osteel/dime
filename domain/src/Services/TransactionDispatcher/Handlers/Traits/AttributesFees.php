<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers\Traits;

use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

trait AttributesFees
{
    private function splitFees(Transaction $transaction): FiatAmount
    {
        // @phpstan-ignore-next-line
        $amount = $transaction->hasFee() ? $transaction->feeMarketValue : $transaction->marketValue->zero();

        // @phpstan-ignore-next-line
        return $transaction->isSwap() && ! $transaction->oneAssetIsFiat() ? $amount->dividedBy('2') : $amount;
    }
}
