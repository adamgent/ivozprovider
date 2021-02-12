<?php

namespace Ivoz\Provider\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Model\Helper\CriteriaHelper;
use Ivoz\Provider\Domain\Model\Domain\DomainInterface;
use Ivoz\Provider\Domain\Model\Terminal\Terminal;
use Ivoz\Provider\Domain\Model\Terminal\TerminalInterface;
use Ivoz\Provider\Domain\Model\Terminal\TerminalRepository;
use Ivoz\Provider\Infrastructure\Persistence\Doctrine\Traits\CountByCriteriaTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * TerminalDoctrineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TerminalDoctrineRepository extends ServiceEntityRepository implements TerminalRepository
{
    use CountByCriteriaTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Terminal::class);
    }

    /**
     * @param string $name
     * @param DomainInterface $domain
     * @return TerminalInterface | null
     */
    public function findOneByNameAndDomain(string $name, DomainInterface $domain)
    {
        /** @var TerminalInterface $response */
        $response = $this->findOneBy([
            'name' => $name,
            'domain' => $domain
        ]);

        return $response;
    }

    /**
     * @return TerminalInterface | null
     */
    public function findOneByCompanyAndName(int $companyId, string $name)
    {
        /** @var TerminalInterface $response */
        $response = $this->findOneBy([
            'name' => $name,
            'company' => $companyId
        ]);

        return $response;
    }

    /**
     * @return TerminalInterface | null
     */
    public function findOneByMac(string $mac)
    {
        /** @var TerminalInterface $response */
        $response = $this->findOneBy([
            'mac' => $mac
        ]);

        return $response;
    }

    /**
     * @param int $companyId
     * @return string[]
     */
    public function findNamesByCompanyId(int $companyId)
    {
        $qb = $this->createQueryBuilder('self');
        $expression = $qb->expr();

        $qb
            ->select('self.name')
            ->where(
                $expression->eq('self.company', $companyId)
            );

        $result = $qb
            ->getQuery()
            ->getScalarResult();

        return array_column(
            $result,
            'name'
        );
    }

    /**
     * @param int[] $companyIds
     * @return int
     */
    public function countRegistrableDevices(array $companyIds): int
    {
        $criteria = CriteriaHelper::fromArray([
            ['company', 'in', $companyIds]
        ]);

        return $this->countByCriteria($criteria);
    }
}
