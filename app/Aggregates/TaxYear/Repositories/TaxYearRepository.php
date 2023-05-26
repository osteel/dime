<?php

declare(strict_types=1);

namespace App\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository as TaxYearRepositoryInterface;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\TaxYearContract;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;

/** @extends EventSourcedAggregateRootRepository<TaxYearContract> */
final class TaxYearRepository extends EventSourcedAggregateRootRepository implements TaxYearRepositoryInterface
{
    public function __construct(
        MessageRepository $messageRepository,
        MessageDispatcher $dispatcher,
        MessageDecorator $decorator,
        ClassNameInflector $classNameInflector,
    ) {
        parent::__construct(TaxYear::class, $messageRepository, $dispatcher, $decorator, $classNameInflector);
    }

    public function get(TaxYearId $taxYearId): TaxYearContract
    {
        return $this->retrieve($taxYearId);
    }

    public function save(TaxYearContract $taxYear): void
    {
        $this->persist($taxYear);
    }
}
