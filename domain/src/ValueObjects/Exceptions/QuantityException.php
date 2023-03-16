<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Exceptions;

use RuntimeException;

final class QuantityException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidQuantity(string $quantity): self
    {
        return new self(sprintf(
            'Invalid quantity %s – the value must be a string representing a positive or negative integer or decimal amount, e.g. "-1.23456789"',
            $quantity,
        ));
    }
}
