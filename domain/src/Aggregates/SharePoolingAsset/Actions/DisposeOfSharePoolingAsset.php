<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\Contracts\Timely;
use Domain\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetRepository;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Stringable;

final readonly class DisposeOfSharePoolingAsset implements Stringable, Timely
{
    public function __construct(
        public Asset $asset,
        public LocalDate $date,
        public Quantity $quantity,
        public FiatAmount $proceeds,
        // Only present whenever the disposal has been reverted and is now being replayed
        public ?SharePoolingAssetTransactionId $transactionId = null,
    ) {
    }

    public function handle(SharePoolingAssetRepository $sharePoolingAssetRepository): void
    {
        $sharePoolingAssetId = SharePoolingAssetId::fromAsset($this->asset);
        $sharePoolingAsset = $sharePoolingAssetRepository->get($sharePoolingAssetId);

        $sharePoolingAsset->disposeOf($this);
        $sharePoolingAssetRepository->save($sharePoolingAsset);
    }

    public function isReplay(): bool
    {
        return ! is_null($this->transactionId);
    }

    public function getDate(): LocalDate
    {
        return $this->date;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (date: %s, quantity: %s, proceeds: %s)',
            self::class,
            (string) $this->date,
            (string) $this->quantity,
            (string) $this->proceeds,
        );
    }
}
