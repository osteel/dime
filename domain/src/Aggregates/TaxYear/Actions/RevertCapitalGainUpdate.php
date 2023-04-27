<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Stringable;

final class RevertCapitalGainUpdate implements Stringable
{
    final public function __construct(
        public readonly LocalDate $date,
        public readonly CapitalGain $capitalGain,
    ) {
    }

    public function handle(TaxYearRepository $taxYearRepository): void
    {
        $taxYearId = TaxYearId::fromDate($this->date);
        $taxYear = $taxYearRepository->get($taxYearId);

        $taxYear->revertCapitalGainUpdate($this);
        $taxYearRepository->save($taxYear);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, capital gain: (%s))',
            self::class,
            (string) $this->date,
            (string) $this->capitalGain,
        );
    }
}
