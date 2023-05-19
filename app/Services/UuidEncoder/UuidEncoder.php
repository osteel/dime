<?php

declare(strict_types=1);

namespace App\Services\UuidEncoder;

use EventSauce\UuidEncoding\UuidEncoder as UuidEncoderInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class UuidEncoder implements UuidEncoderInterface
{
    public function encodeUuid(UuidInterface $uuid): string
    {
        return $uuid->toString();
    }

    public function encodeString(string $uuid): string
    {
        return Uuid::isValid($uuid)
            ? $this->encodeUuid(Uuid::fromString($uuid))
            : $uuid;
    }
}
