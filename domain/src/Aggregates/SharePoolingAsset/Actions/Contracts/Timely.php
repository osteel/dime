<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Actions\Contracts;

use Brick\DateTime\LocalDate;

interface Timely
{
    public function getDate(): LocalDate;
}
