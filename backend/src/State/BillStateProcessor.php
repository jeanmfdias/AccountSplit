<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\BillInput;
use App\Entity\Bill;
use App\Entity\Group;
use App\Entity\Participant;
use App\Enum\SplitType;
use App\Repository\GroupRepository;
use App\Repository\ParticipantRepository;
use App\Exception\InvalidSplitDefinitionException;
use App\Service\BillSharePersister;
use App\Service\SplitCalculator\SplitDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<BillInput, Bill|null>
 */
final class BillStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupRepository $groupRepository,
        private readonly ParticipantRepository $participantRepository,
        private readonly BillSharePersister $billSharePersister,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Bill|null
    {
        if ($operation instanceof Delete) {
            $bill = $context['previous_data'] ?? null;
            if ($bill instanceof Bill) {
                $this->entityManager->remove($bill);
                $this->entityManager->flush();
            }

            return null;
        }

        /** @var BillInput $data */
        $groupId = $uriVariables['groupId'] ?? null;
        $group = $this->groupRepository->find($groupId);

        if (!$group instanceof Group) {
            throw new NotFoundHttpException('Group not found.');
        }

        $participants = $this->loadAndValidateParticipants(
            $data->participantIds,
            $data->paidByParticipantId,
            $group,
        );

        $paidBy = $participants[$data->paidByParticipantId]
            ?? throw new UnprocessableEntityHttpException('Payer participant not found in this group.');

        if ($operation instanceof Post) {
            $bill = new Bill(
                $data->description,
                $data->amountCents,
                $paidBy,
                $data->date ?? new \DateTimeImmutable(),
                $data->splitType,
                $group,
            );
            $this->entityManager->persist($bill);
        } else {
            /** @var Bill $bill */
            $bill = $context['previous_data'];
            $bill->setDescription($data->description);
            $bill->setAmountCents($data->amountCents);
            $bill->setPaidBy($paidBy);
            $bill->setDate($data->date ?? $bill->getDate());
            $bill->setSplitType($data->splitType);
        }

        $definition = new SplitDefinition(
            type: $data->splitType,
            totalAmountCents: $data->amountCents,
            participantIds: $data->participantIds,
            customAmounts: $data->customAmounts,
            percentages: $data->percentages,
        );

        try {
            $this->billSharePersister->persist($bill, $definition, $participants);
        } catch (InvalidSplitDefinitionException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        $this->entityManager->flush();

        return $bill;
    }

    /**
     * @param list<string> $participantIds
     * @return array<string, Participant>
     */
    private function loadAndValidateParticipants(
        array $participantIds,
        string $paidByParticipantId,
        Group $group,
    ): array {
        $allIds = array_unique([...$participantIds, $paidByParticipantId]);

        /** @var Participant[] $found */
        $found = $this->participantRepository->findBy(['id' => $allIds]);

        $byId = [];
        foreach ($found as $participant) {
            if ((string) $participant->getGroup()->getId() !== (string) $group->getId()) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Participant "%s" does not belong to this group.', $participant->getId())
                );
            }
            $byId[(string) $participant->getId()] = $participant;
        }

        foreach ($allIds as $id) {
            if (!isset($byId[$id])) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Participant "%s" not found.', $id)
                );
            }
        }

        return $byId;
    }
}