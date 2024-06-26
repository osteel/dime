<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final readonly class UpdateNonAttributableAllowableCost implements Stringable
{
    public function __construct(
        public LocalDate $date,
        public FiatAmount $nonAttributableAllowableCostChange,
    ) {
    }

    public function __invoke(TaxYearRepository $taxYearRepository): void
    {
        $taxYearId = TaxYearId::fromDate($this->date);
        $taxYear = $taxYearRepository->get($taxYearId);

        $taxYear->updateNonAttributableAllowableCost($this);
        $taxYearRepository->save($taxYear);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, non-attributable allowable cost change: %s)',
            self::class,
            $this->date,
            $this->nonAttributableAllowableCostChange,
        );
    }
}
