<?php

declare(strict_types=1);

namespace App\Aggregates\SharePoolingAsset\Repositories;

use Domain\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetRepository as SharePoolingAssetRepositoryInterface;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAssetId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;

/** @extends EventSourcedAggregateRootRepository<SharePoolingAsset> */
final class SharePoolingAssetRepository extends EventSourcedAggregateRootRepository implements SharePoolingAssetRepositoryInterface
{
    public function __construct(
        MessageRepository $messageRepository,
        MessageDispatcher $dispatcher,
        MessageDecorator $decorator,
        ClassNameInflector $classNameInflector,
    ) {
        parent::__construct(SharePoolingAsset::class, $messageRepository, $dispatcher, $decorator, $classNameInflector);
    }

    public function get(SharePoolingAssetId $sharePoolingAssetId): SharePoolingAsset
    {
        return $this->retrieve($sharePoolingAssetId);
    }

    public function save(SharePoolingAsset $sharePoolingAsset): void
    {
        $this->persist($sharePoolingAsset);
    }
}
