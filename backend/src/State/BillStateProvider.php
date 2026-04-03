<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Bill;
use App\Repository\BillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<Bill>
 */
final class BillStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly BillRepository $billRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $groupId = $uriVariables['groupId'] ?? null;

        if ($operation instanceof GetCollection) {
            return $this->billRepository->findByGroupWithShares(Uuid::fromString((string) $groupId));
        }

        if ($operation instanceof Post) {
            return null;
        }

        $id = $uriVariables['id'] ?? null;

        return $this->entityManager->getRepository(Bill::class)->findOneBy([
            'id' => Uuid::fromString((string) $id),
            'group' => Uuid::fromString((string) $groupId),
        ]);
    }
}
