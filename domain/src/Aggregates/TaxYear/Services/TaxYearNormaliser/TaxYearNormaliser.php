<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Services\TaxYearNormaliser;

use Brick\DateTime\LocalDate;

final class TaxYearNormaliser
{
    private const APRIL = 4;

    public static function fromDate(LocalDate $date): string
    {
        $year = $date->getYear();
        $month = $date->getMonth();
        $day = $date->getDay();

        if ($month < self::APRIL || ($month === self::APRIL && $day < 6)) {
            --$year;
        }

        return sprintf('%s-%s', $year, $year + 1);
    }
}
