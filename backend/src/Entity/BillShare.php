<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BillShareRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BillShareRepository::class)]
class BillShare
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Bill::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Bill $bill;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Participant $participant;

    #[ORM\Column]
    private int $amountCents;

    public function __construct(Bill $bill, Participant $participant, int $amountCents)
    {
        $this->id = Uuid::v7();
        $this->bill = $bill;
        $this->participant = $participant;
        $this->amountCents = $amountCents;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getBill(): Bill
    {
        return $this->bill;
    }

    public function setBill(Bill $bill): self
    {
        $this->bill = $bill;

        return $this;
    }

    public function getParticipant(): Participant
    {
        return $this->participant;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents): self
    {
        $this->amountCents = $amountCents;

        return $this;
    }
}