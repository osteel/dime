<?php

declare(strict_types=1);

namespace Domain\SharePooling\Events;

use Domain\SharePooling\SharePoolingId;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;

final class SharePoolingTokenAcquired
{
    public function __construct(
        public readonly SharePoolingId $sharePoolingId,
        public readonly SharePoolingTokenAcquisition $sharePoolingTokenAcquisition,
    ) {
    }
}
