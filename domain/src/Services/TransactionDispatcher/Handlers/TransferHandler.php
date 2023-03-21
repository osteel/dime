<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\Services\TaxYearNormaliser\TaxYearNormaliser;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\TransferHandlerException;
use Domain\ValueObjects\Transaction;

class TransferHandler
{
    public function __construct(private readonly TaxYearRepository $taxYearRepository)
    {
    }

    /** @throws TransferHandlerException */
    public function handle(Transaction $transaction): void
    {
        $this->validate($transaction);

        if (! $transaction->hasFee()) {
            return;
        }

        $taxYear = TaxYearNormaliser::fromDate($transaction->date);
        $taxYearId = TaxYearId::fromTaxYear($taxYear);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->updateNonAttributableAllowableCost(new UpdateNonAttributableAllowableCost(
            taxYear: $taxYear,
            date: $transaction->date,
            nonAttributableAllowableCost: $transaction->feeMarketValue, // @phpstan-ignore-line
        ));

        $this->taxYearRepository->save($taxYearAggregate);
    }

    /** @throws TransferHandlerException */
    private function validate(Transaction $transaction): void
    {
        $transaction->isTransfer() || throw TransferHandlerException::notTransfer($transaction);
    }
}
