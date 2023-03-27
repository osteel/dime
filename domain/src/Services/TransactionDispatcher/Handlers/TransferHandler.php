<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\TransferHandlerException;
use Domain\ValueObjects\Transactions\Transfer;

class TransferHandler
{
    public function __construct(private readonly TaxYearRepository $taxYearRepository)
    {
    }

    /** @throws TransferHandlerException */
    public function handle(Transfer $transaction): void
    {
        if (is_null($transaction->fee)) {
            return;
        }

        $taxYearId = TaxYearId::fromDate($transaction->date);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->updateNonAttributableAllowableCost(new UpdateNonAttributableAllowableCost(
            date: $transaction->date,
            nonAttributableAllowableCost: $transaction->fee->marketValue,
        ));

        $this->taxYearRepository->save($taxYearAggregate);
    }
}
