<?php

namespace Domain\Tests\SharePooling;

use Domain\SharePooling\Actions\AcquireSharePoolingToken;
use Domain\SharePooling\Actions\DisposeOfSharePoolingToken;
use Domain\SharePooling\SharePooling;
use Domain\SharePooling\SharePoolingId;
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
