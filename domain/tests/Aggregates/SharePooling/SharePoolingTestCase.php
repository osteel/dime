<?php

namespace Domain\Tests\Aggregates\SharePooling;

use Domain\Aggregates\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\Aggregates\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\Aggregates\SharePooling\SharePooling;
use Domain\Aggregates\SharePooling\SharePoolingId;
use Domain\Tests\AggregateRootTestCase;
use EventSauce\EventSourcing\AggregateRootId;

abstract class SharePoolingTestCase extends AggregateRootTestCase
{
    protected function newAggregateRootId(): AggregateRootId
    {
        return SharePoolingId::generate();
    }

    protected function aggregateRootClassName(): string
    {
        return SharePooling::class;
    }

    public function handle(object $action)
    {
        $sharePooling = $this->repository->retrieve($this->aggregateRootId);

        match ($action::class) {
            AcquireSharePoolingToken::class => $sharePooling->acquire($action),
            DisposeOfSharePoolingToken::class => $sharePooling->disposeOf($action),
        };

        $this->repository->persist($sharePooling);
    }
}
