<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findWithParticipants(Uuid $id): ?Group
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.participants', 'p')
            ->addSelect('p')
            ->where('g.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }
}