<?php

declare(strict_types=1);

namespace App\Nft\Repositories;

use Domain\Nft\Nft;
use Domain\Nft\NftId;
use Domain\Nft\Repositories\NftRepository as NftRepositoryInterface;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;

/** @extends EventSourcedAggregateRootRepository<Nft> */
final class NftRepository extends EventSourcedAggregateRootRepository implements NftRepositoryInterface
{
    public function __construct(
        MessageRepository $messageRepository,
        MessageDispatcher $dispatcher,
        MessageDecorator $decorator,
        ClassNameInflector $classNameInflector,
    ) {
        parent::__construct(Nft::class, $messageRepository, $dispatcher, $decorator, $classNameInflector);
    }

    public function get(NftId $nftId): Nft
    {
        return $this->retrieve($nftId);
    }
}
