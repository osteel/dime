<?php

declare(strict_types=1);

namespace Domain\SharePooling\Events;

use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class SharePoolingTokenAcquired implements SerializablePayload
{
    public function __construct(
        public readonly SharePoolingTokenAcquisition $sharePoolingTokenAcquisition,
    ) {
    }

    /** @return array<string, string|array<string, string|array<string, string>>> */
    public function toPayload(): array
    {
        return ['share_pooling_token_acquisition' => $this->sharePoolingTokenAcquisition->toPayload()];
    }

    /** @param array<string, string|array<string, string|array<string, string>>> $payload */
    public static function fromPayload(array $payload): static
    {
        // @phpstan-ignore-next-line
        return new static(SharePoolingTokenAcquisition::fromPayload($payload['share_pooling_token_acquisition']));
    }
}
