<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\ValueObjects\Transactions\Acquisition;

class IncomeHandler
{
    public function __construct(private readonly ActionRunner $runner)
    {
    }

    /** @throws IncomeHandlerException */
    public function handle(Acquisition $transaction): void
    {
        $transaction->isIncome || throw IncomeHandlerException::notIncome($transaction);

        $this->runner->run(new UpdateIncome(
            date: $transaction->date,
            income: $transaction->marketValue,
        ));
    }
}
