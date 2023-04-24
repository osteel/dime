<?php

declare(strict_types=1);

namespace App\Services\TransactionReader\Exceptions;

use RuntimeException;

final class TransactionReaderException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /** @param list<string> $headers */
    public static function missingHeaders(array $headers): self
    {
        return new self(sprintf('Missing headers: [%s]', implode(', ', $headers)));
    }
}
