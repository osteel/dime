<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions\Exceptions;

use Domain\ValueObjects\Transactions\Swap;
use RuntimeException;

final class SwapException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function bothSidesAreFiat(Swap $transaction): self
    {
        return new self(sprintf('Both sides of a swap transaction cannot be fiat: %s', (string) $transaction));
    }
}
