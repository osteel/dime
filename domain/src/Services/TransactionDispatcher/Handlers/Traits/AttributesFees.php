<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers\Traits;

use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

trait AttributesFees
{
    private function splitFees(Transaction $transaction): FiatAmount
    {
        $amount = $transaction->hasNetworkFee()
            ? $transaction->networkFeeMarketValue
            : $transaction->marketValue->nilAmount(); // @phpstan-ignore-line

        if ($transaction->hasPlatformFee()) {
            $amount = $amount->plus($transaction->platformFeeMarketValue); // @phpstan-ignore-line
        }

        // @phpstan-ignore-next-line
        return $transaction->isSwap() && ! $transaction->oneAssetIsFiat() ? $amount->dividedBy('2') : $amount;
    }
}
