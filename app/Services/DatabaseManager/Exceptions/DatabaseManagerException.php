<?php

declare(strict_types=1);

namespace App\Services\DatabaseManager\Exceptions;

use Illuminate\Process\Exceptions\ProcessFailedException;
use RuntimeException;

final class DatabaseManagerException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidDatabaseLocation(string $config): self
    {
        return new self(sprintf('Configuration entry %s is invalid', $config));
    }

    public static function processError(ProcessFailedException $exception): self
    {
        return new self($exception->getMessage());
    }
}
