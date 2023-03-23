<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers\Traits;

use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

trait AttributesFees
{
    private function splitFees(Acquisition | Disposal | Swap $transaction): FiatAmount
    {
        $amount = $transaction->fee?->marketValue ?? $transaction->marketValue->zero();

        return $transaction instanceof Swap && ! $transaction->hasFiat() ? $amount->dividedBy('2') : $amount;
    }
}
