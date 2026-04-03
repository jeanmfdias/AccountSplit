<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\SplitType;
use App\Exception\InvalidSplitDefinitionException;
use App\Service\SplitCalculator;
use App\Service\SplitCalculator\SplitDefinition;
use PHPUnit\Framework\TestCase;

class SplitCalculatorTest extends TestCase
{
    private SplitCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SplitCalculator();
    }

    public function testItSplitsEquallyAmongTwoParticipants(): void
    {
        $definition = new SplitDefinition(
            type: SplitType::Equal,
            totalAmountCents: 200,
            participantIds: ['user-1', 'user-2'],
        );

        $result = $this->calculator->calculate($definition);

        $this->assertSame(100, $result->sharesByCents['user-1']);
        $this->assertSame(100, $result->sharesByCents['user-2']);
    }

    public function testItDistributesRemainderCentsToFirstParticipants(): void
    {
        // 100 cents / 3 = 33 each, remainder 1 → first gets 34
        $definition = new SplitDefinition(
            type: SplitType::Equal,
            totalAmountCents: 100,
            participantIds: ['user-1', 'user-2', 'user-3'],
        );

        $result = $this->calculator->calculate($definition);

        $this->assertSame(34, $result->sharesByCents['user-1']);
        $this->assertSame(33, $result->sharesByCents['user-2']);
        $this->assertSame(33, $result->sharesByCents['user-3']);
        $this->assertSame(100, array_sum($result->sharesByCents));
    }

    public function testItDistributesRemainderAcrossMultipleParticipants(): void
    {
        // 10 cents / 3 = 3 each, remainder 1 → first gets 4
        $definition = new SplitDefinition(
            type: SplitType::Equal,
            totalAmountCents: 10,
            participantIds: ['user-1', 'user-2', 'user-3'],
        );

        $result = $this->calculator->calculate($definition);

        $this->assertSame(10, array_sum($result->sharesByCents));
    }

    public function testEqualSplitSumAlwaysEqualsTotal(): void
    {
        $definition = new SplitDefinition(
            type: SplitType::Equal,
            totalAmountCents: 30000,
            participantIds: ['user-1', 'user-2', 'user-3'],
        );

        $result = $this->calculator->calculate($definition);

        $this->assertSame(30000, array_sum($result->sharesByCents));
        $this->assertSame(10000, $result->sharesByCents['user-1']);
    }

    public function testItSplitsByPercentage(): void
    {
        $definition = new SplitDefinition(
            type: SplitType::Percentage,
            totalAmountCents: 1000,
            participantIds: ['user-1', 'user-2', 'user-3'],
            percentages: ['user-1' => 50.0, 'user-2' => 30.0, 'user-3' => 20.0],
        );

        $result = $this->calculator->calculate($definition);

        $this->assertSame(500, $result->sharesByCents['user-1']);
        $this->assertSame(300, $result->sharesByCents['user-2']);
        $this->assertSame(200, $result->sharesByCents['user-3']);
        $this->assertSame(1000, array_sum($result->sharesByCents));
    }

    public function testPercentageSplitSumAlwaysEqualsTotalAfterRounding(): void
    {
        // 33.33% each of 100 cents = 33 each → sum is 99 → first participant gets +1
        $definition = new SplitDefinition(
            type: SplitType::Percentage,
            totalAmountCents: 100,
            participantIds: ['user-1', 'user-2', 'user-3'],
            percentages: ['user-1' => 33.33, 'user-2' => 33.33, 'user-3' => 33.34],
        );

        $result = $this->calculator->calculate($definition);

        $this->assertSame(100, array_sum($result->sharesByCents));
    }

    public function testItThrowsWhenPercentagesDoNotSumTo100(): void
    {
        $this->expectException(InvalidSplitDefinitionException::class);
        $this->expectExceptionMessageMatches('/100/');

        $definition = new SplitDefinition(
            type: SplitType::Percentage,
            totalAmountCents: 1000,
            participantIds: ['user-1', 'user-2'],
            percentages: ['user-1' => 60.0, 'user-2' => 30.0],
        );

        $this->calculator->calculate($definition);
    }

    public function testItSplitsByCustomAmounts(): void
    {
        $definition = new SplitDefinition(
            type: SplitType::Custom,
            totalAmountCents: 1000,
            participantIds: ['user-1', 'user-2', 'user-3'],
            customAmounts: ['user-1' => 500, 'user-2' => 300, 'user-3' => 200],
        );

        $result = $this->calculator->calculate($definition);

        $this->assertSame(500, $result->sharesByCents['user-1']);
        $this->assertSame(300, $result->sharesByCents['user-2']);
        $this->assertSame(200, $result->sharesByCents['user-3']);
    }

    public function testItThrowsWhenCustomAmountsDoNotSumToTotal(): void
    {
        $this->expectException(InvalidSplitDefinitionException::class);

        $definition = new SplitDefinition(
            type: SplitType::Custom,
            totalAmountCents: 1000,
            participantIds: ['user-1', 'user-2'],
            customAmounts: ['user-1' => 400, 'user-2' => 400],
        );

        $this->calculator->calculate($definition);
    }

    public function testItThrowsWhenParticipantListIsEmpty(): void
    {
        $this->expectException(InvalidSplitDefinitionException::class);

        $definition = new SplitDefinition(
            type: SplitType::Equal,
            totalAmountCents: 1000,
            participantIds: [],
        );

        $this->calculator->calculate($definition);
    }
}
