<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\AggregateRootId;
use Domain\Aggregates\TaxYear\Services\TaxYearNormaliser\TaxYearNormaliser;
use Ramsey\Uuid\Uuid;

final readonly class TaxYearId extends AggregateRootId
{
    private const NAMESPACE = '4c6f1e6b-b69c-4b01-8200-3a73dd49cc9c';

    public static function fromDate(LocalDate $date): static
    {
        $taxYear = TaxYearNormaliser::fromDate($date);

        return self::fromString(Uuid::uuid5(self::NAMESPACE, $taxYear)->toString());
    }
}
