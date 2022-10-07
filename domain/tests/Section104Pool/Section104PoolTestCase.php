<?php

namespace Domain\Tests\Section104Pool;

use Domain\Section104Pool\Actions\AcquireSection104PoolTokens;
use Domain\Section104Pool\Section104Pool;
use Domain\Section104Pool\Section104PoolId;
use Domain\Tests\AggregateRootTestCase;
use EventSauce\EventSourcing\AggregateRootId;

abstract class Section104PoolTestCase extends AggregateRootTestCase
{
    protected function newAggregateRootId(): AggregateRootId
    {
        return Section104PoolId::generate();
    }

    protected function aggregateRootClassName(): string
    {
        return Section104Pool::class;
    }

    public function handle(object $action)
    {
        $section104Pool = $this->repository->retrieve($action->section104PoolId);

        match ($action::class) {
            AcquireSection104PoolTokens::class => $section104Pool->acquire($action),
        };

        $this->repository->persist($section104Pool);
    }
}
