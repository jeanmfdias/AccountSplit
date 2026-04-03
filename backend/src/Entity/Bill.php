<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiResource\BillInput;
use App\Enum\SplitType;
use App\Repository\BillRepository;
use App\State\BillStateProcessor;
use App\State\BillStateProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BillRepository::class)]
#[ApiResource(
    uriTemplate: '/groups/{groupId}/bills',
    operations: [
        new GetCollection(provider: BillStateProvider::class),
        new Post(
            input: BillInput::class,
            processor: BillStateProcessor::class,
        ),
    ],
    uriVariables: ['groupId' => new Link(fromClass: Group::class, toProperty: 'group')],
    normalizationContext: ['groups' => ['bill:read']],
    denormalizationContext: ['groups' => ['bill:write']],
)]
#[ApiResource(
    uriTemplate: '/groups/{groupId}/bills/{id}',
    operations: [
        new Get(provider: BillStateProvider::class),
        new Patch(
            input: BillInput::class,
            provider: BillStateProvider::class,
            processor: BillStateProcessor::class,
        ),
        new Delete(
            provider: BillStateProvider::class,
            processor: BillStateProcessor::class,
        ),
    ],
    uriVariables: [
        'groupId' => new Link(fromClass: Group::class, toProperty: 'group'),
        'id'      => new Link(fromClass: Bill::class),
    ],
    normalizationContext: ['groups' => ['bill:read']],
    denormalizationContext: ['groups' => ['bill:write']],
)]
class Bill
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['bill:read'])]
    #[ApiProperty(identifier: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Groups(['bill:read'])]
    private string $description;

    #[ORM\Column]
    #[Groups(['bill:read'])]
    private int $amountCents;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['bill:read'])]
    private Participant $paidBy;

    #[ORM\Column]
    #[Groups(['bill:read'])]
    private \DateTimeImmutable $date;

    #[ORM\Column(enumType: SplitType::class)]
    #[Groups(['bill:read'])]
    private SplitType $splitType;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'bills')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Group $group;

    /** @var Collection<int, BillShare> */
    #[ORM\OneToMany(targetEntity: BillShare::class, mappedBy: 'bill', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['bill:read'])]
    private Collection $shares;

    public function __construct(
        string $description,
        int $amountCents,
        Participant $paidBy,
        \DateTimeImmutable $date,
        SplitType $splitType,
        Group $group,
    ) {
        $this->id = Uuid::v7();
        $this->description = $description;
        $this->amountCents = $amountCents;
        $this->paidBy = $paidBy;
        $this->date = $date;
        $this->splitType = $splitType;
        $this->group = $group;
        $this->shares = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
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

    public function getPaidBy(): Participant
    {
        return $this->paidBy;
    }

    public function setPaidBy(Participant $paidBy): self
    {
        $this->paidBy = $paidBy;

        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getSplitType(): SplitType
    {
        return $this->splitType;
    }

    public function setSplitType(SplitType $splitType): self
    {
        $this->splitType = $splitType;

        return $this;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): self
    {
        $this->group = $group;

        return $this;
    }

    /** @return Collection<int, BillShare> */
    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function addShare(BillShare $share): self
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setBill($this);
        }

        return $this;
    }

    public function clearShares(): self
    {
        $this->shares->clear();

        return $this;
    }
}