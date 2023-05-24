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
        public readonly FiatAmount $incomeUpdate,
    ) {
    }

    public function handle(TaxYearRepository $taxYearRepository): void
    {
        $taxYearId = TaxYearId::fromDate($this->date);
        $taxYear = $taxYearRepository->get($taxYearId);

        $taxYear->updateIncome($this);
        $taxYearRepository->save($taxYear);
    }

    public function __toString(): string
    {
        return sprintf('%s (date: %s, income: %s)', self::class, $this->date, $this->incomeUpdate);
    }
}
