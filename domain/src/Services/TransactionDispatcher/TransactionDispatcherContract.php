<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher;

use Domain\ValueObjects\Transactions\Transaction;

interface TransactionDispatcherContract
{
    public function dispatch(Transaction $transaction): void;
}
