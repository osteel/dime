<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Reactors;

use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class SharePoolingReactor extends EventConsumer
{
    public function __construct(private readonly TaxYearRepository $taxYearRepository)
    {
    }

    public function handleSharePoolingTokenDisposedOf(SharePoolingTokenDisposedOf $event, Message $message): void
    {
        $disposal = $event->sharePoolingTokenDisposal;
        $taxYearId = TaxYearId::fromDate($disposal->date);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->updateCapitalGain(new UpdateCapitalGain(
            date: $disposal->date,
            capitalGain: new CapitalGain($disposal->costBasis, $disposal->proceeds),
        ));

        $this->taxYearRepository->save($taxYearAggregate);
    }

    public function handleSharePoolingTokenDisposalReverted(SharePoolingTokenDisposalReverted $event, Message $message): void
    {
        $disposal = $event->sharePoolingTokenDisposal;
        $taxYearId = TaxYearId::fromDate($disposal->date);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->revertCapitalGainUpdate(new RevertCapitalGainUpdate(
            date: $disposal->date,
            capitalGain: new CapitalGain($disposal->costBasis, $disposal->proceeds),
        ));

        $this->taxYearRepository->save($taxYearAggregate);
    }
}
