<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /** @return Participant[] */
    public function findByGroup(Uuid $groupId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.group = :groupId')
            ->setParameter('groupId', $groupId, 'uuid')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
