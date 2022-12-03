<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers\Exceptions;

use Domain\ValueObjects\Transaction;
use RuntimeException;

final class TransferHandlerException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidTransaction(string $error, Transaction $transaction): self
    {
        return new self(sprintf('Invalid transaction: %s. Transaction: %s', $error, $transaction->__toString()));
    }
}
