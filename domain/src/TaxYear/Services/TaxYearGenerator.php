<?php

declare(strict_types=1);

namespace Domain\TaxYear\Services;

final class TaxYearGenerator
{
    public static function fromYear(int $year): string
    {
        return sprintf('%s-%s', $year, $year + 1);
    }
}
