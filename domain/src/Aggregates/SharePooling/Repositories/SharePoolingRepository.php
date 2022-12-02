<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Repositories;

use Domain\Aggregates\SharePooling\SharePooling;
use Domain\Aggregates\SharePooling\SharePoolingId;

interface SharePoolingRepository
{
    public function get(SharePoolingId $sharePoolingId): SharePooling;
}
