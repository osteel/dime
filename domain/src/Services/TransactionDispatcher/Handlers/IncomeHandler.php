<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\ValueObjects\Transactions\Acquisition;
use Illuminate\Contracts\Bus\Dispatcher;

class IncomeHandler
{
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /** @throws IncomeHandlerException */
    public function handle(Acquisition $transaction): void
    {
        $transaction->isIncome || throw IncomeHandlerException::notIncome($transaction);

        $this->dispatcher->dispatchSync(new UpdateIncome(
            date: $transaction->date,
            income: $transaction->marketValue,
        ));
    }
}
