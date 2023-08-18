<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Projections\Summary;

interface SummaryRepository
{
    public function get(): ?Summary;
}
