<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use App\Entity\Group;
use App\State\GroupBalanceStateProvider;

#[ApiResource(
    uriTemplate: '/groups/{id}/balance',
    operations: [new Get(provider: GroupBalanceStateProvider::class)],
    uriVariables: ['id' => new Link(fromClass: Group::class)],
)]
final class GroupBalanceOutput
{
    /** @var list<ParticipantBalanceOutput> */
    public array $balances = [];

    /** @var list<TransferOutput> */
    public array $transfers = [];
}
