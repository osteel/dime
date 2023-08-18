<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\Contracts\Timely;
use Domain\Aggregates\NonFungibleAsset\Actions\Contracts\WithAsset;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final readonly class IncreaseNonFungibleAssetCostBasis implements Stringable, Timely, WithAsset
{
    public function __construct(
        public Asset $asset,
        public LocalDate $date,
        public FiatAmount $costBasisIncrease,
        public bool $forFiat,
    ) {
    }

    public function __invoke(NonFungibleAssetRepository $nonFungibleAssetRepository): void
    {
        $nonFungibleAssetId = NonFungibleAssetId::fromAsset($this->asset);
        $nonFungibleAsset = $nonFungibleAssetRepository->get($nonFungibleAssetId);

        $nonFungibleAsset->increaseCostBasis($this);
        $nonFungibleAssetRepository->save($nonFungibleAsset);
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getDate(): LocalDate
    {
        return $this->date;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (asset: %s, date: %s, cost basis increase: %s, for fiat: %s)',
            self::class,
            $this->asset,
            $this->date,
            $this->costBasisIncrease,
            $this->forFiat ? 'yes' : 'no',
        );
    }
}
