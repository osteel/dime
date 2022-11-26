<?php

declare(strict_types=1);

namespace App\TaxYear;

use Domain\TaxYear\Repositories\TaxYearRepository as TaxYearRepositoryInterface;
use Domain\TaxYear\TaxYear;
use Domain\TaxYear\TaxYearId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;

/** @extends EventSourcedAggregateRootRepository<TaxYear> */
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

    public function get(TaxYearId $taxYearId): TaxYear
    {
        return $this->retrieve($taxYearId);
    }

    public function save(TaxYear $taxYear): self
    {
        $this->persist($taxYear);

        return $this;
    }
}
