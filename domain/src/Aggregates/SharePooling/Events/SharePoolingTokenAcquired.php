<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Events;

use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final readonly class SharePoolingTokenAcquired implements SerializablePayload
{
    public function __construct(
        public SharePoolingTokenAcquisition $sharePoolingTokenAcquisition,
    ) {
    }

    /** @return array<string, array<string, string|int|null|array<string, string>>> */
    public function toPayload(): array
    {
        return ['share_pooling_token_acquisition' => $this->sharePoolingTokenAcquisition->toPayload()];
    }

    /** @param array<string, array<string, string|array<string, string>>> $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(SharePoolingTokenAcquisition::fromPayload($payload['share_pooling_token_acquisition']));
    }
}
