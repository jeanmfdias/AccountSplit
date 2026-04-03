<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\GroupBalanceOutput;
use App\ApiResource\ParticipantBalanceOutput;
use App\ApiResource\TransferOutput;
use App\Repository\GroupRepository;
use App\Service\BalanceCalculator;
use NumberFormatter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<GroupBalanceOutput>
 */
final class GroupBalanceStateProvider implements ProviderInterface
{
    private readonly NumberFormatter $formatter;

    public function __construct(
        private readonly GroupRepository $groupRepository,
        private readonly BalanceCalculator $balanceCalculator,
    ) {
        $this->formatter = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GroupBalanceOutput
    {
        $id    = $uriVariables['id'] ?? null;
        $group = $this->groupRepository->findWithBillsAndShares(Uuid::fromString((string) $id));

        if ($group === null) {
            throw new NotFoundHttpException('Group not found.');
        }

        $groupBalance = $this->balanceCalculator->calculate($group);

        $output = new GroupBalanceOutput();

        foreach ($groupBalance->balances as $balance) {
            $item                  = new ParticipantBalanceOutput();
            $item->participantId   = $balance->participantId;
            $item->participantName = $balance->participantName;
            $item->netCents        = $balance->netCents;
            $item->formattedNet    = $this->formatCents($balance->netCents);
            $output->balances[]    = $item;
        }

        foreach ($groupBalance->transfers as $transfer) {
            $item                      = new TransferOutput();
            $item->fromParticipantId   = $transfer->fromParticipantId;
            $item->fromParticipantName = $transfer->fromParticipantName;
            $item->toParticipantId     = $transfer->toParticipantId;
            $item->toParticipantName   = $transfer->toParticipantName;
            $item->amountCents         = $transfer->amountCents;
            $item->formattedAmount     = $this->formatCents($transfer->amountCents);
            $output->transfers[]       = $item;
        }

        return $output;
    }

    private function formatCents(int $cents): string
    {
        return (string) $this->formatter->formatCurrency(abs($cents) / 100, 'BRL');
    }
}