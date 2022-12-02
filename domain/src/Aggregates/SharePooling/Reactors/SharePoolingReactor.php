<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Reactors;

use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalLoss;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalLoss;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\Services\TaxYearGenerator\TaxYearGenerator;
use Domain\Aggregates\TaxYear\TaxYearId;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class SharePoolingReactor extends EventConsumer
{
    public function __construct(private TaxYearRepository $taxYearRepository)
    {
    }

    public function handleSharePoolingTokenDisposedOf(SharePoolingTokenDisposedOf $event, Message $message): void
    {
        $disposal = $event->sharePoolingTokenDisposal;
        $taxYear = TaxYearGenerator::fromYear($disposal->date->getYear());
        $taxYearId = TaxYearId::fromTaxYear($taxYear);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        if ($disposal->proceeds->isGreaterThan($disposal->costBasis)) {
            $taxYearAggregate->recordCapitalGain(new RecordCapitalGain(
                taxYear: $taxYear,
                amount: $disposal->proceeds->minus($disposal->costBasis),
            ));
        } else {
            $taxYearAggregate->recordCapitalLoss(new RecordCapitalLoss(
                taxYear: $taxYear,
                amount: $disposal->costBasis->minus($disposal->proceeds),
            ));
        }

        $this->taxYearRepository->save($taxYearAggregate);
    }

    public function handleSharePoolingTokenDisposalReverted(SharePoolingTokenDisposalReverted $event, Message $message): void
    {
        $disposal = $event->sharePoolingTokenDisposal;
        $taxYear = TaxYearGenerator::fromYear($disposal->date->getYear());
        $taxYearId = TaxYearId::fromTaxYear($taxYear);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        if ($disposal->proceeds->isGreaterThan($disposal->costBasis)) {
            $taxYearAggregate->revertCapitalGain(new RevertCapitalGain(
                taxYear: $taxYear,
                amount: $disposal->proceeds->minus($disposal->costBasis),
            ));
        } else {
            $taxYearAggregate->revertCapitalLoss(new RevertCapitalLoss(
                taxYear: $taxYear,
                amount: $disposal->costBasis->minus($disposal->proceeds),
            ));
        }

        $this->taxYearRepository->save($taxYearAggregate);
    }
}
