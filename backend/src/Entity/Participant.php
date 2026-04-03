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
use App\ApiResource\ParticipantInput;
use App\Repository\ParticipantRepository;
use App\State\ParticipantStateProcessor;
use App\State\ParticipantStateProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[ApiResource(
    uriTemplate: '/groups/{groupId}/participants',
    operations: [
        new GetCollection(provider: ParticipantStateProvider::class),
        new Post(
            input: ParticipantInput::class,
            provider: ParticipantStateProvider::class,
            processor: ParticipantStateProcessor::class,
        ),
    ],
    uriVariables: ['groupId' => new Link(fromClass: Group::class, toProperty: 'group')],
    normalizationContext: ['groups' => ['participant:read']],
)]
#[ApiResource(
    uriTemplate: '/groups/{groupId}/participants/{id}',
    operations: [
        new Get(provider: ParticipantStateProvider::class),
        new Patch(
            input: ParticipantInput::class,
            provider: ParticipantStateProvider::class,
            processor: ParticipantStateProcessor::class,
        ),
        new Delete(
            provider: ParticipantStateProvider::class,
            processor: ParticipantStateProcessor::class,
        ),
    ],
    uriVariables: [
        'groupId' => new Link(fromClass: Group::class, toProperty: 'group'),
        'id' => new Link(fromClass: Participant::class),
    ],
    normalizationContext: ['groups' => ['participant:read']],
)]
class Participant
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['participant:read', 'bill:read'])]
    #[ApiProperty(identifier: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Groups(['participant:read', 'bill:read'])]
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

    #[Groups(['participant:read'])]
    public function getGroupId(): string
    {
        return (string) $this->group->getId();
    }
}
