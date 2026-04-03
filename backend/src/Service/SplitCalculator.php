<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\SplitType;
use App\Exception\InvalidSplitDefinitionException;
use App\Service\SplitCalculator\ShareResult;
use App\Service\SplitCalculator\SplitDefinition;

final class SplitCalculator
{
    public function calculate(SplitDefinition $definition): ShareResult
    {
        if ([] === $definition->participantIds) {
            throw new InvalidSplitDefinitionException('Participant list must not be empty.');
        }

        return match ($definition->type) {
            SplitType::Equal => $this->calculateEqual($definition),
            SplitType::Percentage => $this->calculatePercentage($definition),
            SplitType::Custom => $this->calculateCustom($definition),
        };
    }

    private function calculateEqual(SplitDefinition $definition): ShareResult
    {
        $count = count($definition->participantIds);
        $base = intdiv($definition->totalAmountCents, $count);
        $remainder = $definition->totalAmountCents % $count;

        $shares = [];
        foreach ($definition->participantIds as $i => $id) {
            $shares[$id] = $base + ($i < $remainder ? 1 : 0);
        }

        return new ShareResult($shares);
    }

    private function calculatePercentage(SplitDefinition $definition): ShareResult
    {
        $sum = (float) array_sum($definition->percentages);

        if (abs($sum - 100.0) > 0.01) {
            throw new InvalidSplitDefinitionException(sprintf('Percentages must sum to 100, got %.2f.', $sum));
        }

        $shares = [];
        foreach ($definition->participantIds as $id) {
            $pct = $definition->percentages[$id] ?? 0.0;
            $shares[$id] = (int) round($definition->totalAmountCents * $pct / 100);
        }

        // Distribute rounding error to the first participant
        $diff = $definition->totalAmountCents - (int) array_sum($shares);
        if (0 !== $diff) {
            $firstId = $definition->participantIds[0];
            $shares[$firstId] += $diff;
        }

        return new ShareResult($shares);
    }

    private function calculateCustom(SplitDefinition $definition): ShareResult
    {
        $customSum = (int) array_sum($definition->customAmounts);

        if ($customSum !== $definition->totalAmountCents) {
            throw new InvalidSplitDefinitionException(sprintf('Custom amounts must sum to %d cents, got %d.', $definition->totalAmountCents, $customSum));
        }

        $shares = [];
        foreach ($definition->participantIds as $id) {
            $shares[$id] = $definition->customAmounts[$id] ?? 0;
        }

        return new ShareResult($shares);
    }
}
