<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Reactors;

use Domain\Aggregates\Nft\Events\NftDisposedOf;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalLoss;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\Services\TaxYearNormaliser\TaxYearNormaliser;
use Domain\Aggregates\TaxYear\TaxYearId;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class NftReactor extends EventConsumer
{
    public function __construct(private TaxYearRepository $taxYearRepository)
    {
    }

    public function handleNftDisposedOf(NftDisposedOf $event, Message $message): void
    {
        $taxYear = TaxYearNormaliser::fromYear($event->date->getYear());
        $taxYearId = TaxYearId::fromTaxYear($taxYear);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        if ($event->proceeds->isGreaterThan($event->costBasis)) {
            $taxYearAggregate->recordCapitalGain(new RecordCapitalGain(
                taxYear: $taxYear,
                amount: $event->proceeds->minus($event->costBasis),
            ));
        } else {
            $taxYearAggregate->recordCapitalLoss(new RecordCapitalLoss(
                taxYear: $taxYear,
                amount: $event->costBasis->minus($event->proceeds),
            ));
        }

        $this->taxYearRepository->save($taxYearAggregate);
    }
}
