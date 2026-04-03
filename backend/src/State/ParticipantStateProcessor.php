<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ParticipantInput;
use App\Entity\Group;
use App\Entity\Participant;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ParticipantInput, Participant|null>
 */
final class ParticipantStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupRepository $groupRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Participant
    {
        if ($operation instanceof Delete) {
            $previous = $context['previous_data'] ?? null;
            if ($previous instanceof Participant) {
                $managed = $this->entityManager->find(Participant::class, $previous->getId());
                if (null !== $managed) {
                    $this->entityManager->remove($managed);
                    $this->entityManager->flush();
                }
            }

            return null;
        }

        /** @var ParticipantInput $data */
        $groupId = $uriVariables['groupId'] ?? null;
        $group = $this->groupRepository->find($groupId);

        if (!$group instanceof Group) {
            throw new NotFoundHttpException('Group not found.');
        }

        if ($operation instanceof Post) {
            $participant = new Participant($data->name, $group);
            $this->entityManager->persist($participant);
        } else {
            /** @var Participant $previous */
            $previous = $context['previous_data'];
            $participant = $this->entityManager->find(Participant::class, $previous->getId());
            if (null === $participant) {
                throw new NotFoundHttpException('Participant not found.');
            }
            $participant->setName($data->name);
        }

        $this->entityManager->flush();

        return $participant;
    }
}
