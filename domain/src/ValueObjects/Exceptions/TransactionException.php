<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Exceptions;

use Domain\ValueObjects\Transaction;
use RuntimeException;

final class TransactionException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidData(string $error, Transaction $transaction): self
    {
        return new self(sprintf('Invalid transaction data: %s. Transaction: %s', $error, $transaction->__toString()));
    }
}
