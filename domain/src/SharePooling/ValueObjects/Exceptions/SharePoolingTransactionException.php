<?php

namespace Domain\SharePooling\ValueObjects\Exceptions;

use Domain\SharePooling\ValueObjects\SharePoolingTransaction;
use RuntimeException;

final class SharePoolingTransactionException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function positionAlreadySet(SharePoolingTransaction $transaction, int $position): self
    {
        return new self(sprintf(
            'Attempted to assign position %s but the transaction was assigned position %s. Transaction: %s',
            $position,
            $transaction->getPosition(),
            $transaction->__toString(),
        ));
    }
}
