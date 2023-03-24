<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Actions\Contracts;

use Brick\DateTime\LocalDate;

interface Timely
{
    public function getDate(): LocalDate;
}
