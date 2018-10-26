<?php

namespace Ivoz\Provider\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Ivoz\Provider\Domain\Model\BrandUrl\BrandUrlRepository;
use Ivoz\Provider\Domain\Model\BrandUrl\BrandUrlInterface;
use Ivoz\Provider\Domain\Model\BrandUrl\BrandUrl;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * BrandUrlDoctrineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BrandUrlDoctrineRepository extends ServiceEntityRepository implements BrandUrlRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BrandUrl::class);
    }

    /**
     * @param string $serverName
     * @return BrandUrlInterface | null
     */
    public function findUserUrlByServerName(string $serverName)
    {
        $conditions = [
            'url' => 'https://' . $serverName,
            'urlType' => 'user'
        ];
        /** @var BrandUrlInterface $response */
        $response = $this->findOneBy($conditions);

        return $response;
    }
}
