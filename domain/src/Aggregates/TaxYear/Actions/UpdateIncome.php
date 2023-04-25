<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final class UpdateIncome implements Stringable
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $income,
    ) {
    }

    public function handle(TaxYearRepository $taxYearRepository): void
    {
        $taxYearId = TaxYearId::fromDate($this->date);
        $taxYearAggregate = $taxYearRepository->get($taxYearId);

        $taxYearAggregate->updateIncome($this);

        $taxYearRepository->save($taxYearAggregate);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, income: %s)',
            self::class,
            (string) $this->date,
            (string) $this->income,
        );
    }
}
