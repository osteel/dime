<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Reactors;

use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetDisposedOf;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;
use Illuminate\Contracts\Bus\Dispatcher;

final class NonFungibleAssetReactor extends EventConsumer
{
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    public function handleNonFungibleAssetDisposedOf(NonFungibleAssetDisposedOf $event, Message $message): void
    {
        $this->dispatcher->dispatchSync(new UpdateCapitalGain(
            date: $event->date,
            capitalGain: new CapitalGain($event->costBasis, $event->proceeds),
        ));
    }
}
