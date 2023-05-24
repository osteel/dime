<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Projectors;

use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\Projectors\Exceptions\TaxYearSummaryProjectionException;
use Domain\Aggregates\TaxYear\Repositories\TaxYearSummaryRepository;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class TaxYearSummaryProjector extends EventConsumer
{
    public function __construct(private readonly TaxYearSummaryRepository $taxYearSummaryRepository)
    {
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleCapitalGainUpdated(CapitalGainUpdated $event, Message $message): void
    {
        $this->taxYearSummaryRepository->updateCapitalGain(
            taxYearId: $this->getTaxYearId($message),
            capitalGain: $event->capitalGainUpdate,
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleCapitalGainUpdateReverted(CapitalGainUpdateReverted $event, Message $message): void
    {
        $this->taxYearSummaryRepository->updateCapitalGain(
            taxYearId: $this->getTaxYearId($message),
            capitalGain: $event->capitalGainUpdate->opposite(),
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleIncomeUpdated(IncomeUpdated $event, Message $message): void
    {
        $this->taxYearSummaryRepository->updateIncome(
            taxYearId: $this->getTaxYearId($message),
            income: $event->incomeUpdate,
        );
    }

    /** @throws TaxYearSummaryProjectionException */
    public function handleNonAttributableAllowableCostUpdated(
        NonAttributableAllowableCostUpdated $event,
        Message $message,
    ): void {
        $this->taxYearSummaryRepository->updateNonAttributableAllowableCost(
            taxYearId: $this->getTaxYearId($message),
            nonAttributableAllowableCost: $event->nonAttributableAllowableCost,
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
