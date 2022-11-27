<?php

declare(strict_types=1);

namespace Domain\Nft\Reactors;

use Domain\Nft\Events\NftDisposedOf;
use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RecordCapitalLoss;
use Domain\TaxYear\Repositories\TaxYearRepository;
use Domain\TaxYear\TaxYearId;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class NftReactor extends EventConsumer
{
    public function __construct(private TaxYearRepository $taxYearRepository)
    {
    }

    public function handleNftDisposedOf(NftDisposedOf $event, Message $message): void
    {
        $taxYearId = TaxYearId::fromYear($event->date->getYear());
        $taxYear = $this->taxYearRepository->get($taxYearId);

        if ($event->proceeds->isGreaterThan($event->costBasis)) {
            $taxYear->recordCapitalGain(new RecordCapitalGain(amount: $event->proceeds->minus($event->costBasis)));
        } else {
            $taxYear->recordCapitalLoss(new RecordCapitalLoss(amount: $event->costBasis->minus($event->proceeds)));
        }

        $this->taxYearRepository->save($taxYear);
    }
}
