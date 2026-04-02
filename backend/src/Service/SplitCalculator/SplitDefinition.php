<?php

declare(strict_types=1);

namespace App\Service\SplitCalculator;

use App\Enum\SplitType;

final readonly class SplitDefinition
{
    /**
     * @param list<string>            $participantIds UUIDs as strings
     * @param array<string, int>      $customAmounts  key = UUID string, value = cents (Custom type)
     * @param array<string, float>    $percentages    key = UUID string, value = percentage (Percentage type)
     */
    public function __construct(
        public SplitType $type,
        public int $totalAmountCents,
        public array $participantIds,
        public array $customAmounts = [],
        public array $percentages = [],
    ) {
    }
}