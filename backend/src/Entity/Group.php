<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['group:read']],
    denormalizationContext: ['groups' => ['group:write']],
)]
class Group
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['group:read'])]
    #[ApiProperty(identifier: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Groups(['group:read', 'group:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column]
    #[Groups(['group:read'])]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Participant> */
    #[ORM\OneToMany(targetEntity: Participant::class, mappedBy: 'group', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participants;

    /** @var Collection<int, Bill> */
    #[ORM\OneToMany(targetEntity: Bill::class, mappedBy: 'group', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bills;

    public function __construct(string $name)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
        $this->participants = new ArrayCollection();
        $this->bills = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['group:read'])]
    public function getParticipantCount(): int
    {
        return $this->participants->count();
    }

    /** @return Collection<int, Participant> */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setGroup($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /** @return Collection<int, Bill> */
    public function getBills(): Collection
    {
        return $this->bills;
    }

    public function addBill(Bill $bill): self
    {
        if (!$this->bills->contains($bill)) {
            $this->bills->add($bill);
            $bill->setGroup($this);
        }

        return $this;
    }

    public function removeBill(Bill $bill): self
    {
        $this->bills->removeElement($bill);

        return $this;
    }
}
