<?php

declare(strict_types=1);

namespace Domain\Services\Math\Exceptions;

use RuntimeException;
use Throwable;

final class MathException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fromThrowable(Throwable $exception): self
    {
        return new self($exception->getMessage());
    }
}
