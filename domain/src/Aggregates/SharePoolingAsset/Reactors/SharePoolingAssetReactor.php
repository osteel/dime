<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Reactors;

use Domain\Actions\UpdateSummary;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Services\ActionRunner\ActionRunner;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class SharePoolingAssetReactor extends EventConsumer
{
    public function __construct(private readonly ActionRunner $runner)
    {
    }

    public function handleSharePoolingAssetAcquired(SharePoolingAssetAcquired $event, Message $message): void
    {
        if ($event->acquisition->forFiat) {
            $this->runner->run(new UpdateSummary(
                fiatBalanceUpdate: $event->acquisition->costBasis->minus($event->acquisition->costBasis->multipliedBy('2')),
            ));
        }
    }

    public function handleSharePoolingAssetDisposedOf(SharePoolingAssetDisposedOf $event, Message $message): void
    {
        $this->runner->run(new UpdateCapitalGain(
            date: $event->disposal->date,
            capitalGainUpdate: new CapitalGain($event->disposal->costBasis, $event->disposal->proceeds),
        ));

        if ($event->disposal->forFiat) {
            $this->runner->run(new UpdateSummary(fiatBalanceUpdate: $event->disposal->proceeds));
        }
    }

    public function handleSharePoolingAssetDisposalReverted(SharePoolingAssetDisposalReverted $event, Message $message): void
    {
        $this->runner->run(new RevertCapitalGainUpdate(
            date: $event->disposal->date,
            capitalGainUpdate: new CapitalGain($event->disposal->costBasis, $event->disposal->proceeds),
        ));

        if ($event->disposal->forFiat) {
            $this->runner->run(new UpdateSummary(
                fiatBalanceUpdate: $event->disposal->proceeds->minus($event->disposal->proceeds->multipliedBy('2')),
            ));
        }
    }
}
