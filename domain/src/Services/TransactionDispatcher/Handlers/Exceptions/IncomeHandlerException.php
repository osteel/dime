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

    public static function operationIsNotReceive(Transaction $transaction): self
    {
        return new self(sprintf('The transaction is not a receive operation: %s', $transaction->__toString()));
    }

    public static function notIncome(Transaction $transaction): self
    {
        return new self(sprintf('The transaction is not flagged as income: %s', $transaction->__toString()));
    }
}
