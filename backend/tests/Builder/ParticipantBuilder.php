<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Entity\Group;
use App\Entity\Participant;

final class ParticipantBuilder
{
    private string $name = 'Test Participant';
    private ?Group $group = null;

    public static function new(): self
    {
        return new self();
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function withGroup(Group $group): self
    {
        $clone = clone $this;
        $clone->group = $group;

        return $clone;
    }

    public function build(): Participant
    {
        $group = $this->group ?? GroupBuilder::new()->build();

        return new Participant($this->name, $group);
    }
}