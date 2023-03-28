<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions;

use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransaction;
use RuntimeException;

final class QuantityBreakdownException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function unassignableTransaction(SharePoolingAssetTransaction $transaction): self
    {
        return new self(sprintf(
            'Can only assign quantities to processed transactions. Transaction: %s',
            $transaction->__toString(),
        ));
    }
}
