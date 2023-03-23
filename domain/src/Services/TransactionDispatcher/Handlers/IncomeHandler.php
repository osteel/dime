<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\Services\TaxYearNormaliser\TaxYearNormaliser;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\ValueObjects\Transactions\Acquisition;

class IncomeHandler
{
    public function __construct(private readonly TaxYearRepository $taxYearRepository)
    {
    }

    /** @throws IncomeHandlerException */
    public function handle(Acquisition $transaction): void
    {
        $transaction->isIncome || throw IncomeHandlerException::notIncome($transaction);

        $taxYear = TaxYearNormaliser::fromDate($transaction->date);
        $taxYearId = TaxYearId::fromTaxYear($taxYear);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->updateIncome(new UpdateIncome(
            taxYear: $taxYear,
            date: $transaction->date,
            income: $transaction->marketValue,
        ));

        $this->taxYearRepository->save($taxYearAggregate);
    }
}
