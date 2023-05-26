<?php

declare(strict_types=1);

namespace App\Aggregates\NonFungibleAsset\Repositories;

use Domain\Aggregates\NonFungibleAsset\NonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAssetContract;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository as NonFungibleAssetRepositoryInterface;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;

/** @extends EventSourcedAggregateRootRepository<NonFungibleAssetContract> */
final class NonFungibleAssetRepository extends EventSourcedAggregateRootRepository implements NonFungibleAssetRepositoryInterface
{
    public function __construct(
        MessageRepository $messageRepository,
        MessageDispatcher $dispatcher,
        MessageDecorator $decorator,
        ClassNameInflector $classNameInflector,
    ) {
        parent::__construct(NonFungibleAsset::class, $messageRepository, $dispatcher, $decorator, $classNameInflector);
    }

    public function get(NonFungibleAssetId $nonFungibleAssetId): NonFungibleAssetContract
    {
        return $this->retrieve($nonFungibleAssetId);
    }

    public function save(NonFungibleAssetContract $nonFungibleAsset): void
    {
        $this->persist($nonFungibleAsset);
    }
}
