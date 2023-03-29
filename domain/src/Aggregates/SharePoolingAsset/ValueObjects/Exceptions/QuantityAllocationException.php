<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions;

use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransaction;
use RuntimeException;

final class QuantityAllocationException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function unprocessedTransaction(SharePoolingAssetTransaction $transaction): self
    {
        return new self(sprintf(
            'Can only allocate quantities to processed transactions. Transaction: %s',
            $transaction->__toString(),
        ));
    }
}
