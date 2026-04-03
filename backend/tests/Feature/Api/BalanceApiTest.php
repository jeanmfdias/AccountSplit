<?php

declare(strict_types=1);

namespace App\Tests\Feature\Api;

use App\Tests\Feature\ApiTestCase;

class BalanceApiTest extends ApiTestCase
{
    public function test_it_returns_balance_for_a_group_with_bills(): void
    {
        [$group, $participants] = $this->createGroupWithParticipants('Trip', ['Alice', 'Bob', 'Carlos']);
        $pids = array_column($participants, 'id');

        // Bill 1: R$120, paid by Alice, equal split → each owes R$40
        $this->json('POST', "/api/groups/{$group['id']}/bills", [
            'description'         => 'Hotel',
            'amountCents'         => 12000,
            'paidByParticipantId' => $participants['Alice']['id'],
            'date'                => '2026-01-15T00:00:00+00:00',
            'splitType'           => 'equal',
            'participantIds'      => $pids,
        ]);

        // Bill 2: R$60, paid by Bob, equal split → each owes R$20
        $this->json('POST', "/api/groups/{$group['id']}/bills", [
            'description'         => 'Taxi',
            'amountCents'         => 6000,
            'paidByParticipantId' => $participants['Bob']['id'],
            'date'                => '2026-01-16T00:00:00+00:00',
            'splitType'           => 'equal',
            'participantIds'      => $pids,
        ]);

        $data = $this->json('GET', "/api/groups/{$group['id']}/balance");

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('balances', $data);
        $this->assertArrayHasKey('transfers', $data);
        $this->assertCount(3, $data['balances']);

        // Alice: paid 12000, share 6000 → net +6000
        // Bob:   paid 6000,  share 6000 → net 0
        // Carlos: paid 0,   share 6000 → net -6000
        $netsByName = [];
        foreach ($data['balances'] as $balance) {
            $this->assertArrayHasKey('participantId', $balance);
            $this->assertArrayHasKey('participantName', $balance);
            $this->assertArrayHasKey('netCents', $balance);
            $this->assertArrayHasKey('formattedNet', $balance);
            $netsByName[$balance['participantName']] = $balance['netCents'];
        }

        $this->assertSame(6000, $netsByName['Alice']);
        $this->assertSame(0, $netsByName['Bob']);
        $this->assertSame(-6000, $netsByName['Carlos']);

        // 1 transfer: Carlos → Alice R$60
        $this->assertCount(1, $data['transfers']);
        $transfer = $data['transfers'][0];
        $this->assertSame('Carlos', $transfer['fromParticipantName']);
        $this->assertSame('Alice', $transfer['toParticipantName']);
        $this->assertSame(6000, $transfer['amountCents']);
        $this->assertArrayHasKey('formattedAmount', $transfer);
    }

    public function test_it_returns_empty_balance_for_group_with_no_bills(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Empty Group']);

        $data = $this->json('GET', "/api/groups/{$group['id']}/balance");

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $data['balances']);
        $this->assertSame([], $data['transfers']);
    }

    public function test_it_returns_404_for_unknown_group_balance(): void
    {
        $this->json('GET', '/api/groups/01962222-0000-7000-8000-000000000000/balance');

        $this->assertResponseStatusCodeSame(404);
    }
}