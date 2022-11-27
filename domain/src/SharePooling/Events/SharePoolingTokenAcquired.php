<?php

declare(strict_types=1);

namespace Domain\SharePooling\Events;

use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;

final class SharePoolingTokenAcquired
{
    public function __construct(
        public readonly SharePoolingTokenAcquisition $sharePoolingTokenAcquisition,
    ) {
    }
}
