<?php

declare(strict_types=1);

namespace App\Service\SplitCalculator;

final readonly class ShareResult
{
    /**
     * @param array<string, int> $sharesByCents key = participant UUID string, value = cents
     */
    public function __construct(
        public array $sharesByCents,
    ) {
    }
}
