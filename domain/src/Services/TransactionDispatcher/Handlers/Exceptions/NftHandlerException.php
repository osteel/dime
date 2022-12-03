<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers\Exceptions;

use Domain\ValueObjects\Transaction;
use RuntimeException;

final class NftHandlerException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function unsupportedOperation(Transaction $transaction): self
    {
        return new self(sprintf('Unsupported transaction operation: %s', $transaction->__toString()));
    }

    public static function noNft(Transaction $transaction): self
    {
        return new self(sprintf('Neither asset is a NFT: %s', $transaction->__toString()));
    }
}
