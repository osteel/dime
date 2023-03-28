<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions;

use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransaction;
use RuntimeException;

final class SharePoolingAssetTransactionException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function positionAlreadySet(SharePoolingAssetTransaction $transaction, int $position): self
    {
        return new self(sprintf(
            'Attempted to assign position %s but the transaction was assigned position %s. Transaction: %s',
            $position,
            $transaction->getPosition(),
            $transaction->__toString(),
        ));
    }
}
