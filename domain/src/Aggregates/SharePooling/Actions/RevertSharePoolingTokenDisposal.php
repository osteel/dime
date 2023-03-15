<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Actions;

use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;

final readonly class RevertSharePoolingTokenDisposal
{
    public function __construct(
        public SharePoolingTokenDisposal $sharePoolingTokenDisposal,
    ) {
    }
}
