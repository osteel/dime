<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers\Exceptions;

use Domain\ValueObjects\Transaction;
use RuntimeException;

final class IncomeHandlerException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidTransaction(Transaction $transaction): self
    {
        return new self($transaction->__toString());
    }
}
