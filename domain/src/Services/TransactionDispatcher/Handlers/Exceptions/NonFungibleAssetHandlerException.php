<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers\Exceptions;

use Domain\ValueObjects\Transactions\Transaction;
use RuntimeException;

final class NonFungibleAssetHandlerException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function noNonFungibleAsset(Transaction $transaction): self
    {
        return new self(sprintf('Neither asset is a non-fungible asset: %s', (string) $transaction));
    }
}
