<?php

declare(strict_types=1);

namespace Domain\TaxYear;

use Domain\AggregateRootId;
use Ramsey\Uuid\Uuid;

final class TaxYearId extends AggregateRootId
{
    private const NAMESPACE = '4c6f1e6b-b69c-4b01-8200-3a73dd49cc9c';

    public static function fromTaxYear(string $taxYear): static
    {
        return self::fromString(Uuid::uuid5(self::NAMESPACE, $taxYear)->toString());
    }
}
