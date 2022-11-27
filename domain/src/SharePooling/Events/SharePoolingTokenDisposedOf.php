<?php

declare(strict_types=1);

namespace Domain\SharePooling\Events;

use Domain\SharePooling\ValueObjects\SharePoolingTokenDisposal;

final class SharePoolingTokenDisposedOf
{
    public function __construct(
        public readonly SharePoolingTokenDisposal $sharePoolingTokenDisposal,
    ) {
    }
}
