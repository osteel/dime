<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Reactors;

use Domain\Aggregates\Nft\Events\NftDisposedOf;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class NftReactor extends EventConsumer
{
    public function __construct(private readonly TaxYearRepository $taxYearRepository)
    {
    }

    public function handleNftDisposedOf(NftDisposedOf $event, Message $message): void
    {
        $taxYearId = TaxYearId::fromDate($event->date);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->updateCapitalGain(new UpdateCapitalGain(
            date: $event->date,
            capitalGain: new CapitalGain($event->costBasis, $event->proceeds),
        ));

        $this->taxYearRepository->save($taxYearAggregate);
    }
}
