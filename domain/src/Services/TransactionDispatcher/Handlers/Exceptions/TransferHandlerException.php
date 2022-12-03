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

    public static function notTransfer(Transaction $transaction): self
    {
        return new self(sprintf('The transaction is not a transfer operation: %s', $transaction->__toString()));
    }
}
