<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Bill;
use App\Entity\BillShare;
use App\Enum\SplitType;
use App\Service\BalanceCalculator;
use App\Tests\Builder\GroupBuilder;
use App\Tests\Builder\ParticipantBuilder;
use PHPUnit\Framework\TestCase;

class BalanceCalculatorTest extends TestCase
{
    private BalanceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new BalanceCalculator();
    }

    public function testItReturnsEmptyResultForGroupWithNoBills(): void
    {
        $group = GroupBuilder::new()->build();

        $result = $this->calculator->calculate($group);

        $this->assertSame([], $result->balances);
        $this->assertSame([], $result->transfers);
    }

    public function testItComputesCorrectNetBalanceAfterEqualSplit(): void
    {
        $group = GroupBuilder::new()->build();
        $alice = ParticipantBuilder::new()->withName('Alice')->withGroup($group)->build();
        $bob = ParticipantBuilder::new()->withName('Bob')->withGroup($group)->build();

        // Alice pays R$100, equal split → Alice 50, Bob 50
        $bill = new Bill('Dinner', 10000, $alice, new \DateTimeImmutable(), SplitType::Equal, $group);
        $bill->addShare(new BillShare($bill, $alice, 5000));
        $bill->addShare(new BillShare($bill, $bob, 5000));
        $group->addBill($bill);

        $result = $this->calculator->calculate($group);

        $balanceByName = [];
        foreach ($result->balances as $b) {
            $balanceByName[$b->participantName] = $b->netCents;
        }

        $this->assertSame(5000, $balanceByName['Alice']);  // paid 10000, owes 5000 → +5000
        $this->assertSame(-5000, $balanceByName['Bob']);   // paid 0, owes 5000 → -5000
    }

    public function testItComputesMinimalTransfersForTwoParticipants(): void
    {
        $group = GroupBuilder::new()->build();
        $alice = ParticipantBuilder::new()->withName('Alice')->withGroup($group)->build();
        $bob = ParticipantBuilder::new()->withName('Bob')->withGroup($group)->build();

        $bill = new Bill('Hotel', 10000, $alice, new \DateTimeImmutable(), SplitType::Equal, $group);
        $bill->addShare(new BillShare($bill, $alice, 5000));
        $bill->addShare(new BillShare($bill, $bob, 5000));
        $group->addBill($bill);

        $result = $this->calculator->calculate($group);

        $this->assertCount(1, $result->transfers);
        $this->assertSame('Bob', $result->transfers[0]->fromParticipantName);
        $this->assertSame('Alice', $result->transfers[0]->toParticipantName);
        $this->assertSame(5000, $result->transfers[0]->amountCents);
    }

    public function testItComputesMinimalTransfersForThreeParticipants(): void
    {
        // Bill 1: R$120 (Alice pays), equal 3-way → each owes R$40
        // Bill 2: R$60  (Bob pays),   equal 3-way → each owes R$20
        // Net: Alice +80, Bob +40, Carlos -120  → Carlos sends R$80 to Alice, Carlos sends R$40 to Bob
        // Actually let's compute:
        // Alice: paid 120, owes 40+20 = 60 → net +60
        // Bob:   paid 60,  owes 40+20 = 60 → net 0
        // Carlos: paid 0,  owes 40+20 = 60 → net -60
        // Transfer: Carlos → Alice R$60

        $group = GroupBuilder::new()->build();
        $alice = ParticipantBuilder::new()->withName('Alice')->withGroup($group)->build();
        $bob = ParticipantBuilder::new()->withName('Bob')->withGroup($group)->build();
        $carlos = ParticipantBuilder::new()->withName('Carlos')->withGroup($group)->build();

        $bill1 = new Bill('Hotel', 12000, $alice, new \DateTimeImmutable(), SplitType::Equal, $group);
        $bill1->addShare(new BillShare($bill1, $alice, 4000));
        $bill1->addShare(new BillShare($bill1, $bob, 4000));
        $bill1->addShare(new BillShare($bill1, $carlos, 4000));
        $group->addBill($bill1);

        $bill2 = new Bill('Dinner', 6000, $bob, new \DateTimeImmutable(), SplitType::Equal, $group);
        $bill2->addShare(new BillShare($bill2, $alice, 2000));
        $bill2->addShare(new BillShare($bill2, $bob, 2000));
        $bill2->addShare(new BillShare($bill2, $carlos, 2000));
        $group->addBill($bill2);

        $result = $this->calculator->calculate($group);

        $balanceByName = [];
        foreach ($result->balances as $b) {
            $balanceByName[$b->participantName] = $b->netCents;
        }

        $this->assertSame(6000, $balanceByName['Alice']);
        $this->assertSame(0, $balanceByName['Bob']);
        $this->assertSame(-6000, $balanceByName['Carlos']);

        // Bob is settled → only 1 transfer needed
        $this->assertCount(1, $result->transfers);
        $this->assertSame('Carlos', $result->transfers[0]->fromParticipantName);
        $this->assertSame('Alice', $result->transfers[0]->toParticipantName);
        $this->assertSame(6000, $result->transfers[0]->amountCents);
    }

    public function testItHandlesParticipantWithZeroNetBalance(): void
    {
        $group = GroupBuilder::new()->build();
        $alice = ParticipantBuilder::new()->withName('Alice')->withGroup($group)->build();
        $bob = ParticipantBuilder::new()->withName('Bob')->withGroup($group)->build();
        $carlos = ParticipantBuilder::new()->withName('Carlos')->withGroup($group)->build();

        // Bob pays exactly what he owes — net 0 for Bob
        $bill1 = new Bill('Hotel', 9000, $alice, new \DateTimeImmutable(), SplitType::Equal, $group);
        $bill1->addShare(new BillShare($bill1, $alice, 3000));
        $bill1->addShare(new BillShare($bill1, $bob, 3000));
        $bill1->addShare(new BillShare($bill1, $carlos, 3000));
        $group->addBill($bill1);

        // Bob pays 3000 for Alice and Carlos only — Bob's net from bill1 (-3000) cancels bill2 (+3000)
        $bill2 = new Bill('Snack', 3000, $bob, new \DateTimeImmutable(), SplitType::Equal, $group);
        $bill2->addShare(new BillShare($bill2, $alice, 1500));
        $bill2->addShare(new BillShare($bill2, $carlos, 1500));
        $group->addBill($bill2);

        $result = $this->calculator->calculate($group);

        // Carlos must send Alice 3000; Bob is settled
        $nonZeroTransfers = array_filter(
            $result->transfers,
            fn ($t) => 'Bob' === $t->fromParticipantName || 'Bob' === $t->toParticipantName
        );

        $this->assertCount(0, $nonZeroTransfers);
    }

    public function testItProducesAtMostNMinusOneTransfers(): void
    {
        $group = GroupBuilder::new()->build();
        $participants = [];
        for ($i = 1; $i <= 5; ++$i) {
            $participants[$i] = ParticipantBuilder::new()->withName("User $i")->withGroup($group)->build();
        }

        // User 1 pays everything, split equally
        $bill = new Bill('Big Bill', 50000, $participants[1], new \DateTimeImmutable(), SplitType::Equal, $group);
        foreach ($participants as $p) {
            $bill->addShare(new BillShare($bill, $p, 10000));
        }
        $group->addBill($bill);

        $result = $this->calculator->calculate($group);

        $this->assertLessThanOrEqual(4, count($result->transfers)); // n-1 = 5-1
    }

    public function testItHandlesComplexMultiBillScenario(): void
    {
        // From PROJECT_DEFINITIONS example:
        // Hotel R$300 paid by Alice, equal 3-way → Alice 100, Bob 100, Carlos 100
        // Dinner R$100 paid by Bob, custom → Alice 50, Bob 30, Carlos 20
        // Net: Alice +150, Bob -30, Carlos -120
        // Transfers: Bob→Alice 30, Carlos→Alice 120

        $group = GroupBuilder::new()->build();
        $alice = ParticipantBuilder::new()->withName('Alice')->withGroup($group)->build();
        $bob = ParticipantBuilder::new()->withName('Bob')->withGroup($group)->build();
        $carlos = ParticipantBuilder::new()->withName('Carlos')->withGroup($group)->build();

        $hotel = new Bill('Hotel', 30000, $alice, new \DateTimeImmutable(), SplitType::Equal, $group);
        $hotel->addShare(new BillShare($hotel, $alice, 10000));
        $hotel->addShare(new BillShare($hotel, $bob, 10000));
        $hotel->addShare(new BillShare($hotel, $carlos, 10000));
        $group->addBill($hotel);

        $dinner = new Bill('Dinner', 10000, $bob, new \DateTimeImmutable(), SplitType::Custom, $group);
        $dinner->addShare(new BillShare($dinner, $alice, 5000));
        $dinner->addShare(new BillShare($dinner, $bob, 3000));
        $dinner->addShare(new BillShare($dinner, $carlos, 2000));
        $group->addBill($dinner);

        $result = $this->calculator->calculate($group);

        $balanceByName = [];
        foreach ($result->balances as $b) {
            $balanceByName[$b->participantName] = $b->netCents;
        }

        $this->assertSame(15000, $balanceByName['Alice']);
        $this->assertSame(-3000, $balanceByName['Bob']);
        $this->assertSame(-12000, $balanceByName['Carlos']);

        $this->assertCount(2, $result->transfers);

        $fromNames = array_map(fn ($t) => $t->fromParticipantName, $result->transfers);
        $this->assertContains('Bob', $fromNames);
        $this->assertContains('Carlos', $fromNames);

        foreach ($result->transfers as $transfer) {
            $this->assertSame('Alice', $transfer->toParticipantName);
        }
    }
}
