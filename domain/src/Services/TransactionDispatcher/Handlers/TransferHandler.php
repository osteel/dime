<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\TransferHandlerException;
use Domain\ValueObjects\Transactions\Transfer;

class TransferHandler
{
    public function __construct(private readonly ActionRunner $runner)
    {
    }

    /** @throws TransferHandlerException */
    public function handle(Transfer $transaction): void
    {
        if (is_null($transaction->fee)) {
            return;
        }

        $this->runner->run(new UpdateNonAttributableAllowableCost(
            date: $transaction->date,
            nonAttributableAllowableCost: $transaction->fee->marketValue,
        ));
    }
}
