<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Reactors;

use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class SharePoolingAssetReactor extends EventConsumer
{
    public function __construct(private readonly TaxYearRepository $taxYearRepository)
    {
    }

    public function handleSharePoolingAssetDisposedOf(SharePoolingAssetDisposedOf $event, Message $message): void
    {
        $disposal = $event->sharePoolingAssetDisposal;
        $taxYearId = TaxYearId::fromDate($disposal->date);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->updateCapitalGain(new UpdateCapitalGain(
            date: $disposal->date,
            capitalGain: new CapitalGain($disposal->costBasis, $disposal->proceeds),
        ));

        $this->taxYearRepository->save($taxYearAggregate);
    }

    public function handleSharePoolingAssetDisposalReverted(SharePoolingAssetDisposalReverted $event, Message $message): void
    {
        $disposal = $event->sharePoolingAssetDisposal;
        $taxYearId = TaxYearId::fromDate($disposal->date);
        $taxYearAggregate = $this->taxYearRepository->get($taxYearId);

        $taxYearAggregate->revertCapitalGainUpdate(new RevertCapitalGainUpdate(
            date: $disposal->date,
            capitalGain: new CapitalGain($disposal->costBasis, $disposal->proceeds),
        ));

        $this->taxYearRepository->save($taxYearAggregate);
    }
}
