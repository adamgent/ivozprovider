<?php

namespace Tests\Provider\Administrator;

use Ivoz\Provider\Infrastructure\Persistence\Doctrine\AdministratorDoctrineRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\DbIntegrationTestHelperTrait;
use Ivoz\Provider\Domain\Model\Administrator\Administrator;

class AdministratorRepositoryTest extends KernelTestCase
{
    use DbIntegrationTestHelperTrait;

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_throws_exception_on_no_results()
    {
        /** @var AdministratorDoctrineRepository $repository */
        $repository = $this->em
            ->getRepository(Administrator::class);

        $repository->getInnerGlobalAdmin();
    }
}