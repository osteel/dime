<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear;

use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use EventSauce\EventSourcing\AggregateRoot;

/** @extends AggregateRoot<TaxYearId> */
interface TaxYearContract extends AggregateRoot
{
    public function updateCapitalGain(UpdateCapitalGain $action): void;

    public function revertCapitalGainUpdate(RevertCapitalGainUpdate $action): void;

    public function updateIncome(UpdateIncome $action): void;

    public function updateNonAttributableAllowableCost(UpdateNonAttributableAllowableCost $action): void;
}
