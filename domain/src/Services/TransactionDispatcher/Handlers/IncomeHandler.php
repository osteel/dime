<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\TaxYear\Actions\RecordIncome;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\Services\TaxYearGenerator\TaxYearGenerator;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\ValueObjects\Transaction;

class IncomeHandler
{
    public function __construct(private TaxYearRepository $taxYearRepository)
    {
    }

    /** @throws IncomeHandlerException */
    public function handle(Transaction $transaction): void
    {
        $this->validate($transaction);

        $taxYear = TaxYearGenerator::fromYear($transaction->date->getYear());
        $taxYearId = TaxYearId::fromTaxYear($taxYear);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->recordIncome(new RecordIncome(taxYear: $taxYear, amount: $transaction->costBasis));
    }

    /** @throws IncomeHandlerException */
    private function validate(Transaction $transaction): void
    {
        $transaction->isReceive() || throw IncomeHandlerException::invalidTransaction($transaction);
    }
}
