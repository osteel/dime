<?php

namespace Domain\Tests\Aggregates;

use App\Action;
use Domain\Aggregates\NftId;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

abstract class NftTestCase extends AggregateRootTestCase
{
    protected function newAggregateRootId(): AggregateRootId
    {
        return NftId::generate();
    }

    protected function aggregateRootClassName(): string
    {
        return Nft::class;
    }

    public function handle(Action $action)
    {
        $action->handle($this->repository);
    }
}
