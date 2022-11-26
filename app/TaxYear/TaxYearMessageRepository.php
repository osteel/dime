<?php

declare(strict_types=1);

namespace App\TaxYear;

use Domain\TaxYear\Repositories\TaxYearMessageRepository as TaxYearMessageRepositoryInterface;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateUuidV4MessageRepository;

class TaxYearMessageRepository extends IlluminateUuidV4MessageRepository implements TaxYearMessageRepositoryInterface
{
}
