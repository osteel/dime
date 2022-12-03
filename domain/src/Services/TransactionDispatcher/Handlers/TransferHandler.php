<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\Services\TaxYearGenerator\TaxYearGenerator;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\TransferHandlerException;
use Domain\ValueObjects\Transaction;

class TransferHandler
{
    public function __construct(private TaxYearRepository $taxYearRepository)
    {
    }

    /** @throws TransferHandlerException */
    public function handle(Transaction $transaction): void
    {
        $this->validate($transaction);

        if ($this->transactionHasNoFee($transaction)) {
            return;
        }

        $taxYear = TaxYearGenerator::fromYear($transaction->date->getYear());
        $taxYearId = TaxYearId::fromTaxYear($taxYear);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        if ($transaction->transactionFeeCostBasis?->isGreaterThan('0')) {
            $taxYearAggregate->recordNonAttributableAllowableCost(new RecordNonAttributableAllowableCost(
                taxYear: $taxYear,
                amount: $transaction->transactionFeeCostBasis,
            ));
        }

        if ($transaction->exchangeFeeCostBasis?->isGreaterThan('0')) {
            $taxYearAggregate->recordNonAttributableAllowableCost(new RecordNonAttributableAllowableCost(
                taxYear: $taxYear,
                amount: $transaction->exchangeFeeCostBasis,
            ));
        }
    }

    /** @throws TransferHandlerException */
    private function validate(Transaction $transaction): void
    {
        $transaction->isTransfer() || throw TransferHandlerException::invalidTransaction('not transfer', $transaction);
    }

    private function transactionHasNoFee(Transaction $transaction): bool
    {
        return (bool) ($transaction->transactionFeeCostBasis?->isGreaterThan('0')) === false
            && (bool) ($transaction->exchangeFeeCostBasis?->isGreaterThan('0')) === false;
    }
}