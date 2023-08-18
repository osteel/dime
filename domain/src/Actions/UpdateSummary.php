<?php

declare(strict_types=1);

namespace Domain\Actions;

use Domain\Projections\Summary;
use Domain\ValueObjects\FiatAmount;

final class UpdateSummary
{
    final public function __construct(public readonly FiatAmount $fiatBalanceUpdate)
    {
    }

    public function __invoke(): void
    {
        $summary = Summary::firstOrNew([], [
            'currency' => $this->fiatBalanceUpdate->currency,
            'fiat_balance' => new FiatAmount('0', $this->fiatBalanceUpdate->currency),
        ]);

        $summary->fill(['fiat_balance' => $summary->fiat_balance->plus($this->fiatBalanceUpdate)])->save();
    }
}
