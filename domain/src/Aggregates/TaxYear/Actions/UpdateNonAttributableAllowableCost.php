<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final class UpdateNonAttributableAllowableCost implements Stringable
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly FiatAmount $nonAttributableAllowableCost,
    ) {
    }

    public function handle(TaxYearRepository $taxYearRepository): void
    {
        $taxYearId = TaxYearId::fromDate($this->date);
        $taxYearAggregate = $taxYearRepository->get($taxYearId);

        $taxYearAggregate->updateNonAttributableAllowableCost($this);

        $taxYearRepository->save($taxYearAggregate);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, non-attributable allowable cost: %s)',
            self::class,
            (string) $this->date,
            (string) $this->nonAttributableAllowableCost,
        );
    }
}
