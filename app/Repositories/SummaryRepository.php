<?php

declare(strict_types=1);

namespace App\Repositories;

use Domain\Projections\Summary;
use Domain\Repositories\SummaryRepository as SummaryRepositoryInterface;

final class SummaryRepository implements SummaryRepositoryInterface
{
    public function get(): ?Summary
    {
        return Summary::first();
    }
}
