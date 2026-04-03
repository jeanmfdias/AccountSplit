<?php

declare(strict_types=1);

namespace App\Service\BalanceCalculator;

final readonly class GroupBalance
{
    /**
     * @param list<ParticipantBalance> $balances
     * @param list<Transfer>           $transfers
     */
    public function __construct(
        public array $balances,
        public array $transfers,
    ) {
    }
}
