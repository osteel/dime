<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions\Exceptions;

use Domain\ValueObjects\Transactions\Disposal;
use RuntimeException;

final class DisposalException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function isFiat(Disposal $transaction): self
    {
        return new self(sprintf('The disposed of asset cannot be fiat: %s', (string) $transaction));
    }
}
