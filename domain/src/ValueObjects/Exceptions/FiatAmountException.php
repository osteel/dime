<?php

namespace Domain\ValueObjects\Exceptions;

use RuntimeException;

final class FiatAmountException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fiatCurrenciesDoNotMatch(string ...$currencies): self
    {
        return new self(sprintf('The fiat currencies don\'t match. Found %s.', implode(', ', $currencies)));
    }
}
