<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Reactors;

use Domain\Actions\UpdateSummary;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetAcquired;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetCostBasisIncreased;
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

    public function handleNonFungibleAssetAcquired(NonFungibleAssetAcquired $event, Message $message): void
    {
        if ($event->forFiat) {
            $this->runner->run(
                new UpdateSummary(fiatBalanceUpdate: $event->costBasis->minus($event->costBasis->multipliedBy('2'))),
            );
        }
    }

    public function handleNonFungibleAssetCostBasisIncreased(NonFungibleAssetCostBasisIncreased $event, Message $message): void
    {
        if ($event->forFiat) {
            $this->runner->run(
                new UpdateSummary(fiatBalanceUpdate: $event->costBasisIncrease->minus($event->costBasisIncrease->multipliedBy('2'))),
            );
        }
    }

    public function handleNonFungibleAssetDisposedOf(NonFungibleAssetDisposedOf $event, Message $message): void
    {
        $this->runner->run(new UpdateCapitalGain(
            date: $event->date,
            capitalGainUpdate: new CapitalGain($event->costBasis, $event->proceeds),
        ));

        if ($event->forFiat) {
            $this->runner->run(new UpdateSummary(fiatBalanceUpdate: $event->proceeds));
        }
    }
}
