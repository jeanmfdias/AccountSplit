<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Group $group;

    public function __construct(string $name, Group $group)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->group = $group;
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

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): self
    {
        $this->group = $group;

        return $this;
    }
}