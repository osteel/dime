<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Projectors;

use Domain\Aggregates\TaxYear\Events\CapitalGainRecorded;
use Domain\Aggregates\TaxYear\Events\CapitalGainReverted;
use Domain\Aggregates\TaxYear\Events\CapitalLossRecorded;
use Domain\Aggregates\TaxYear\Events\CapitalLossReverted;
use Domain\Aggregates\TaxYear\Events\IncomeRecorded;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostRecorded;
use Domain\Aggregates\TaxYear\Projectors\Exceptions\TaxYearSummaryProjectionException;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository;
use Domain\Aggregates\TaxYear\TaxYearId;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class TaxYearSummaryProjector extends EventConsumer
{
    public function __construct(private readonly TaxYearSummaryRepository $taxYearSummaryRepository)
    {
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleCapitalGainRecorded(CapitalGainRecorded $event, Message $message): void
    {
        $this->taxYearSummaryRepository->recordCapitalGain(
            $this->getTaxYearId($message),
            $event->taxYear,
            $event->amount,
            $event->costBasis,
            $event->proceeds,
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleCapitalGainReverted(CapitalGainReverted $event, Message $message): void
    {
        $this->taxYearSummaryRepository->revertCapitalGain(
            $this->getTaxYearId($message),
            $event->taxYear,
            $event->amount,
            $event->costBasis,
            $event->proceeds,
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleCapitalLossRecorded(CapitalLossRecorded $event, Message $message): void
    {
        $this->taxYearSummaryRepository->recordCapitalLoss(
            $this->getTaxYearId($message),
            $event->taxYear,
            $event->amount,
            $event->costBasis,
            $event->proceeds,
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleCapitalLossReverted(CapitalLossReverted $event, Message $message): void
    {
        $this->taxYearSummaryRepository->revertCapitalLoss(
            $this->getTaxYearId($message),
            $event->taxYear,
            $event->amount,
            $event->costBasis,
            $event->proceeds,
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleIncomeRecorded(IncomeRecorded $event, Message $message): void
    {
        $this->taxYearSummaryRepository->recordIncome(
            $this->getTaxYearId($message),
            $event->taxYear,
            $event->amount,
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleNonAttributableAllowableCostRecorded(
        NonAttributableAllowableCostRecorded $event,
        Message $message,
    ): void {
        $this->taxYearSummaryRepository->recordNonAttributableAllowableCost(
            $this->getTaxYearId($message),
            $event->taxYear,
            $event->amount,
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    private function getTaxYearId(Message $message): TaxYearId
    {
        if (is_null($taxYearId = $message->aggregateRootId()?->toString())) {
            throw TaxYearSummaryProjectionException::missingTaxYearId($message->payload()::class);
        }

        return TaxYearId::fromString($taxYearId);
    }
}
