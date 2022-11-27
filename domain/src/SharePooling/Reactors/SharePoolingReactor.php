<?php

declare(strict_types=1);

namespace Domain\SharePooling\Reactors;

use Domain\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RecordCapitalLoss;
use Domain\TaxYear\Actions\RevertCapitalGain;
use Domain\TaxYear\Actions\RevertCapitalLoss;
use Domain\TaxYear\Repositories\TaxYearRepository;
use Domain\TaxYear\TaxYearId;
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
        $taxYearId = TaxYearId::fromYear($disposal->date->getYear());
        $taxYear = $this->taxYearRepository->get($taxYearId);

        if ($disposal->proceeds->isGreaterThan($disposal->costBasis)) {
            $taxYear->recordCapitalGain(new RecordCapitalGain(amount: $disposal->proceeds->minus($disposal->costBasis)));
        } else {
            $taxYear->recordCapitalLoss(new RecordCapitalLoss(amount: $disposal->costBasis->minus($disposal->proceeds)));
        }

        $this->taxYearRepository->save($taxYear);
    }

    public function handleSharePoolingTokenDisposalReverted(SharePoolingTokenDisposalReverted $event, Message $message): void
    {
        $disposal = $event->sharePoolingTokenDisposal;
        $taxYearId = TaxYearId::fromYear($disposal->date->getYear());
        $taxYear = $this->taxYearRepository->get($taxYearId);

        if ($disposal->proceeds->isGreaterThan($disposal->costBasis)) {
            $taxYear->revertCapitalGain(new RevertCapitalGain(amount: $disposal->proceeds->minus($disposal->costBasis)));
        } else {
            $taxYear->revertCapitalLoss(new RevertCapitalLoss(amount: $disposal->costBasis->minus($disposal->proceeds)));
        }

        $this->taxYearRepository->save($taxYear);
    }
}
