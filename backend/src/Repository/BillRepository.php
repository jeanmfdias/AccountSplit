<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Bill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Bill>
 */
class BillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bill::class);
    }

    /**
     * Loads all bills for a group with shares and participants in a single JOIN FETCH query.
     * Avoids N+1 queries on the balance calculation endpoint.
     *
     * @return Bill[]
     */
    public function findByGroupWithShares(Uuid $groupId): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.shares', 's')
            ->addSelect('s')
            ->leftJoin('s.participant', 'sp')
            ->addSelect('sp')
            ->leftJoin('b.paidBy', 'pb')
            ->addSelect('pb')
            ->where('b.group = :groupId')
            ->setParameter('groupId', $groupId, 'uuid')
            ->orderBy('b.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}