<?php

declare(strict_types=1);

namespace App\Aggregates\TaxYear\Repositories;

use Domain\Aggregates\TaxYear\Repositories\TaxYearMessageRepository as TaxYearMessageRepositoryInterface;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateUuidV4MessageRepository;

final class TaxYearMessageRepository extends IlluminateUuidV4MessageRepository implements TaxYearMessageRepositoryInterface
{
}
