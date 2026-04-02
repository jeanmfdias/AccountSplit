<?php

declare(strict_types=1);

namespace App\Service\BalanceCalculator;

final readonly class Transfer
{
    public function __construct(
        public string $fromParticipantId,
        public string $fromParticipantName,
        public string $toParticipantId,
        public string $toParticipantName,
        public int $amountCents,
    ) {
    }
}