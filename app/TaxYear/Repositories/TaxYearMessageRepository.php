<?php

declare(strict_types=1);

namespace App\TaxYear\Repositories;

use Domain\TaxYear\Repositories\TaxYearMessageRepository as TaxYearMessageRepositoryInterface;
use Domain\TaxYear\TaxYearId;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateUuidV4MessageRepository;
use Generator;

class TaxYearMessageRepository extends IlluminateUuidV4MessageRepository implements TaxYearMessageRepositoryInterface
{
    public function all(TaxYearId $taxYearId): Generator
    {
        return $this->retrieveAll($taxYearId);
    }
}
