<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\ValueObjects\Exceptions\TaxYearIdException;
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

    public static function fromString(string $aggregateRootId): static
    {
        $years = explode('-', $aggregateRootId);

        if (count($years) !== 2 || (int) $years[1] !== (int) $years[0] + 1) {
            throw TaxYearIdException::invalidTaxYear();
        }

        return parent::fromString($aggregateRootId);
    }
}
