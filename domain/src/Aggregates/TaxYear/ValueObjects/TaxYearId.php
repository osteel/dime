<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\AggregateRootId;

final readonly class TaxYearId extends AggregateRootId
{
    private const APRIL = 4;

    public static function fromDate(LocalDate $date): static
    {
        $year = $date->getYear();
        $month = $date->getMonth();
        $day = $date->getDay();

        if ($month < self::APRIL || ($month === self::APRIL && $day < 6)) {
            --$year;
        }

        return self::fromString(sprintf('%s-%s', $year, $year + 1));
    }
}
