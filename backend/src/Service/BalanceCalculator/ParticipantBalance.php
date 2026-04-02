<?php

declare(strict_types=1);

namespace App\Service\BalanceCalculator;

final readonly class ParticipantBalance
{
    public function __construct(
        public string $participantId,
        public string $participantName,
        public int $netCents,
    ) {
    }
}