<?php

declare(strict_types=1);

namespace Domain\SharePooling\Repositories;

use Domain\SharePooling\SharePooling;
use Domain\SharePooling\SharePoolingId;

interface SharePoolingRepository
{
    public function get(SharePoolingId $sharePoolingId): SharePooling;
}
