<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Bill;
use App\Entity\BillShare;
use App\Entity\Participant;
use App\Service\SplitCalculator\SplitDefinition;
use Doctrine\ORM\EntityManagerInterface;

final class BillSharePersister
{
    public function __construct(
        private readonly SplitCalculator $splitCalculator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Recalculates and persists BillShare rows for a bill.
     * Does NOT flush — the caller owns the flush.
     *
     * @param array<string, Participant> $participantsById key = UUID string
     */
    public function persist(Bill $bill, SplitDefinition $definition, array $participantsById): void
    {
        $bill->clearShares();

        $result = $this->splitCalculator->calculate($definition);

        foreach ($result->sharesByCents as $participantId => $amountCents) {
            $participant = $participantsById[$participantId]
                ?? throw new \InvalidArgumentException(sprintf('Participant "%s" not found.', $participantId));

            $share = new BillShare($bill, $participant, $amountCents);
            $this->entityManager->persist($share);
            $bill->addShare($share);
        }
    }
}