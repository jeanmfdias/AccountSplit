<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Entity\Bill;
use App\Entity\BillShare;
use App\Entity\Group;
use App\Entity\Participant;
use App\Enum\SplitType;

final class BillBuilder
{
    private string $description = 'Test Bill';
    private int $amountCents = 10000;
    private ?Participant $paidBy = null;
    private \DateTimeImmutable $date;
    private SplitType $splitType = SplitType::Equal;
    private ?Group $group = null;
    /** @var array<string, array{0: Participant, 1: int}> participantId => [Participant, amountCents] */
    private array $shares = [];

    public function __construct()
    {
        $this->date = new \DateTimeImmutable();
    }

    public static function new(): self
    {
        return new self();
    }

    public function withDescription(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withAmountCents(int $amountCents): self
    {
        $clone = clone $this;
        $clone->amountCents = $amountCents;

        return $clone;
    }

    public function withPaidBy(Participant $paidBy): self
    {
        $clone = clone $this;
        $clone->paidBy = $paidBy;

        return $clone;
    }

    public function withGroup(Group $group): self
    {
        $clone = clone $this;
        $clone->group = $group;

        return $clone;
    }

    public function withSplitType(SplitType $splitType): self
    {
        $clone = clone $this;
        $clone->splitType = $splitType;

        return $clone;
    }

    /** @param array<int, array{0: Participant, 1: int}> $shares list of [Participant, amountCents] pairs */
    public function withShares(array $shares): self
    {
        $clone = clone $this;
        foreach ($shares as [$participant, $cents]) {
            $clone->shares[(string) $participant->getId()] = [$participant, $cents];
        }

        return $clone;
    }

    public function build(): Bill
    {
        $group = $this->group ?? GroupBuilder::new()->build();
        $paidBy = $this->paidBy ?? ParticipantBuilder::new()->withGroup($group)->build();

        $bill = new Bill(
            $this->description,
            $this->amountCents,
            $paidBy,
            $this->date,
            $this->splitType,
            $group,
        );

        foreach ($this->shares as [$participant, $cents]) {
            $share = new BillShare($bill, $participant, $cents);
            $bill->addShare($share);
        }

        return $bill;
    }
}
