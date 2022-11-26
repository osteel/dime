<?php

namespace Domain\Tests\Nft\Reactors;

use Domain\Nft\Reactors\NftReactor;
use Domain\TaxYear\Repositories\TaxYearRepository;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;
use Mockery;
use Mockery\MockInterface;

class NftReactorTestCase extends MessageConsumerTestCase
{
    protected MockInterface $taxYearRepository;

    public function messageConsumer(): MessageConsumer
    {
        $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);

        return new NftReactor($this->taxYearRepository);
    }
}
