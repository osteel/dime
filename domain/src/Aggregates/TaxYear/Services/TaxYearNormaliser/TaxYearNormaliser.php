<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Services\TaxYearNormaliser;

final class TaxYearNormaliser
{
    public static function fromYear(int $year): string
    {
        return sprintf('%s-%s', $year, $year + 1);
    }
}
