<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Entity\Group;

final class GroupBuilder
{
    private string $name = 'Test Group';

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

    public function build(): Group
    {
        return new Group($this->name);
    }
}
