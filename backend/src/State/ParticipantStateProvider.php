<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<Participant>
 */
final class ParticipantStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly ParticipantRepository $participantRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $groupId = $uriVariables['groupId'] ?? null;

        if ($operation instanceof GetCollection) {
            return $this->participantRepository->findBy([
                'group' => Uuid::fromString((string) $groupId),
            ]);
        }

        if ($operation instanceof Post) {
            return null;
        }

        $id = $uriVariables['id'] ?? null;

        return $this->participantRepository->findOneBy([
            'id'    => Uuid::fromString((string) $id),
            'group' => Uuid::fromString((string) $groupId),
        ]);
    }
}