<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Group;
use App\Service\BalanceCalculator\GroupBalance;
use App\Service\BalanceCalculator\ParticipantBalance;
use App\Service\BalanceCalculator\Transfer;

final class BalanceCalculator
{
    public function calculate(Group $group): GroupBalance
    {
        /** @var array<string, array{name: string, net: int}> $nets */
        $nets = [];

        foreach ($group->getBills() as $bill) {
            $payerId = (string) $bill->getPaidBy()->getId();

            if (!isset($nets[$payerId])) {
                $nets[$payerId] = ['name' => $bill->getPaidBy()->getName(), 'net' => 0];
            }

            // Payer gets credit for the full bill
            $nets[$payerId]['net'] += $bill->getAmountCents();

            // Each participant in the bill is debited their share
            foreach ($bill->getShares() as $share) {
                $participantId = (string) $share->getParticipant()->getId();

                if (!isset($nets[$participantId])) {
                    $nets[$participantId] = ['name' => $share->getParticipant()->getName(), 'net' => 0];
                }

                $nets[$participantId]['net'] -= $share->getAmountCents();
            }
        }

        $balances = array_map(
            fn (string $id, array $data) => new ParticipantBalance($id, $data['name'], $data['net']),
            array_keys($nets),
            $nets,
        );

        $transfers = $this->minimizeTransfers($nets);

        return new GroupBalance($balances, $transfers);
    }

    /**
     * Greedy two-pointer algorithm: matches largest debtor to largest creditor.
     * Produces at most n-1 transfers for n participants with non-zero balances.
     *
     * @param array<string, array{name: string, net: int}> $nets
     *
     * @return list<Transfer>
     */
    private function minimizeTransfers(array $nets): array
    {
        /** @var array<string, array{name: string, net: int}> $debtors */
        $debtors = [];
        /** @var array<string, array{name: string, net: int}> $creditors */
        $creditors = [];

        foreach ($nets as $id => $data) {
            if ($data['net'] < 0) {
                $debtors[$id] = ['name' => $data['name'], 'net' => $data['net']];
            } elseif ($data['net'] > 0) {
                $creditors[$id] = ['name' => $data['name'], 'net' => $data['net']];
            }
        }

        // Sort debtors ascending (most negative first)
        uasort($debtors, fn (array $a, array $b) => $a['net'] <=> $b['net']);
        // Sort creditors descending (most positive first)
        uasort($creditors, fn (array $a, array $b) => $b['net'] <=> $a['net']);

        /** @var array<string, array{name: string, net: int}> $debtors */
        /** @var array<string, array{name: string, net: int}> $creditors */
        $debtorIds = array_keys($debtors);
        $creditorIds = array_keys($creditors);
        $di = 0;
        $ci = 0;
        $transfers = [];

        while ($di < count($debtorIds) && $ci < count($creditorIds)) {
            $dId = $debtorIds[$di];
            $cId = $creditorIds[$ci];

            $amount = min(abs($debtors[$dId]['net']), $creditors[$cId]['net']);

            $transfers[] = new Transfer(
                fromParticipantId: $dId,
                fromParticipantName: $debtors[$dId]['name'],
                toParticipantId: $cId,
                toParticipantName: $creditors[$cId]['name'],
                amountCents: $amount,
            );

            $debtors[$dId]['net'] += $amount;
            $creditors[$cId]['net'] -= $amount;

            if (0 === $debtors[$dId]['net']) {
                ++$di;
            }

            if (0 === $creditors[$cId]['net']) {
                ++$ci;
            }
        }

        return $transfers;
    }
}
