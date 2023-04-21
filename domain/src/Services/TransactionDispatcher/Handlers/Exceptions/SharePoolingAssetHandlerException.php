<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers\Exceptions;

use Domain\ValueObjects\Transactions\Transaction;
use RuntimeException;

final class SharePoolingAssetHandlerException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function noSharePoolingAsset(Transaction $transaction): self
    {
        return new self(sprintf('This transaction does not include a share pooling asset: %s', (string) $transaction));
    }
}
