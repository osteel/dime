<?php

declare(strict_types=1);

namespace App\SharePooling\Repositories;

use Domain\SharePooling\Repositories\SharePoolingRepository as SharePoolingRepositoryInterface;
use Domain\SharePooling\SharePooling;
use Domain\SharePooling\SharePoolingId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;

/** @extends EventSourcedAggregateRootRepository<SharePooling> */
final class SharePoolingRepository extends EventSourcedAggregateRootRepository implements SharePoolingRepositoryInterface
{
    public function __construct(
        MessageRepository $messageRepository,
        MessageDispatcher $dispatcher,
        MessageDecorator $decorator,
        ClassNameInflector $classNameInflector,
    ) {
        parent::__construct(SharePooling::class, $messageRepository, $dispatcher, $decorator, $classNameInflector);
    }

    public function get(SharePoolingId $sharePoolingId): SharePooling
    {
        return $this->retrieve($sharePoolingId);
    }
}
