<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Reactors;

use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;
use Illuminate\Contracts\Bus\Dispatcher;

final class SharePoolingAssetReactor extends EventConsumer
{
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    public function handleSharePoolingAssetDisposedOf(SharePoolingAssetDisposedOf $event, Message $message): void
    {
        $this->dispatcher->dispatchSync(new UpdateCapitalGain(
            date: $event->disposal->date,
            capitalGain: new CapitalGain($event->disposal->costBasis, $event->disposal->proceeds),
        ));
    }

    public function handleSharePoolingAssetDisposalReverted(SharePoolingAssetDisposalReverted $event, Message $message): void
    {
        $this->dispatcher->dispatchSync(new RevertCapitalGainUpdate(
            date: $event->disposal->date,
            capitalGain: new CapitalGain($event->disposal->costBasis, $event->disposal->proceeds),
        ));
    }
}
