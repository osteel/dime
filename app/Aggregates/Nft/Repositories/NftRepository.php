<?php

declare(strict_types=1);

namespace App\Aggregates\Nft\Repositories;

use Domain\Aggregates\Nft\Nft;
use Domain\Aggregates\Nft\NftId;
use Domain\Aggregates\Nft\Repositories\NftRepository as NftRepositoryInterface;
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

    public function save(Nft $nft): void
    {
        $this->persist($nft);
    }
}
