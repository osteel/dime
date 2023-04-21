<?php

declare(strict_types=1);

namespace App\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\Repositories\TaxYearMessageRepository as TaxYearMessageRepositoryInterface;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateUuidV4MessageRepository;
use Generator;

class TaxYearMessageRepository extends IlluminateUuidV4MessageRepository implements TaxYearMessageRepositoryInterface
{
    public function all(TaxYearId $taxYearId): Generator
    {
        return $this->retrieveAll($taxYearId);
    }
}
