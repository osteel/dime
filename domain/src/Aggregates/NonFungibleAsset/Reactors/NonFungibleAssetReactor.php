<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Reactors;

use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetDisposedOf;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Services\ActionRunner\ActionRunner;
use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\Message;

final class NonFungibleAssetReactor extends EventConsumer
{
    public function __construct(private readonly ActionRunner $runner)
    {
    }

    public function handleNonFungibleAssetDisposedOf(NonFungibleAssetDisposedOf $event, Message $message): void
    {
        $this->runner->run(new UpdateCapitalGain(
            date: $event->date,
            capitalGain: new CapitalGain($event->costBasis, $event->proceeds),
        ));
    }
}
