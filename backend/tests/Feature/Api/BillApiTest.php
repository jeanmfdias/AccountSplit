<?php

declare(strict_types=1);

namespace App\Tests\Feature\Api;

use App\Tests\Feature\ApiTestCase;

class BillApiTest extends ApiTestCase
{
    public function testItCreatesABillWithEqualSplitAndMaterializesShares(): void
    {
        [$group, $participants] = $this->createGroupWithParticipants('Trip', ['Alice', 'Bob', 'Carlos']);

        $payload = [
            'description' => 'Hotel',
            'amountCents' => 30000,
            'paidByParticipantId' => $participants['Alice']['id'],
            'date' => '2026-01-15T00:00:00+00:00',
            'splitType' => 'equal',
            'participantIds' => array_column($participants, 'id'),
        ];

        $data = $this->json('POST', "/api/groups/{$group['id']}/bills", $payload);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Hotel', $data['description']);
        $this->assertSame(30000, $data['amountCents']);
        $this->assertCount(3, $data['shares']);
        $this->assertSame(30000, array_sum(array_column($data['shares'], 'amountCents')));

        foreach ($data['shares'] as $share) {
            $this->assertSame(10000, $share['amountCents']);
        }
    }

    public function testItCreatesABillWithCustomAmounts(): void
    {
        [$group, $participants] = $this->createGroupWithParticipants('Trip', ['Alice', 'Bob', 'Carlos']);

        $aliceId = $participants['Alice']['id'];
        $bobId = $participants['Bob']['id'];
        $carlosId = $participants['Carlos']['id'];

        $payload = [
            'description' => 'Dinner',
            'amountCents' => 10000,
            'paidByParticipantId' => $bobId,
            'date' => '2026-01-16T00:00:00+00:00',
            'splitType' => 'custom',
            'participantIds' => [$aliceId, $bobId, $carlosId],
            'customAmounts' => [$aliceId => 5000, $bobId => 3000, $carlosId => 2000],
        ];

        $data = $this->json('POST', "/api/groups/{$group['id']}/bills", $payload);

        $this->assertResponseStatusCodeSame(201);

        $sharesByParticipant = [];
        foreach ($data['shares'] as $share) {
            $sharesByParticipant[$share['participantId']] = $share['amountCents'];
        }

        $this->assertSame(5000, $sharesByParticipant[$aliceId]);
        $this->assertSame(3000, $sharesByParticipant[$bobId]);
        $this->assertSame(2000, $sharesByParticipant[$carlosId]);
    }

    public function testItReturns422WhenCustomAmountsDoNotSumToTotal(): void
    {
        [$group, $participants] = $this->createGroupWithParticipants('Trip', ['Alice', 'Bob']);

        $payload = [
            'description' => 'Snack',
            'amountCents' => 1000,
            'paidByParticipantId' => $participants['Alice']['id'],
            'date' => '2026-01-16T00:00:00+00:00',
            'splitType' => 'custom',
            'participantIds' => array_column($participants, 'id'),
            'customAmounts' => [
                $participants['Alice']['id'] => 400,
                $participants['Bob']['id'] => 400,
            ],
        ];

        $this->json('POST', "/api/groups/{$group['id']}/bills", $payload);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testItUpdatesBillAndRecalculatesShares(): void
    {
        [$group, $participants] = $this->createGroupWithParticipants('Trip', ['Alice', 'Bob', 'Carlos']);

        $aliceId = $participants['Alice']['id'];
        $bobId   = $participants['Bob']['id'];
        $carlosId = $participants['Carlos']['id'];

        $bill = $this->json('POST', "/api/groups/{$group['id']}/bills", [
            'description'          => 'Hotel',
            'amountCents'          => 30000,
            'paidByParticipantId'  => $aliceId,
            'date'                 => '2026-01-15T00:00:00+00:00',
            'splitType'            => 'equal',
            'participantIds'       => [$aliceId, $bobId, $carlosId],
        ]);

        $this->assertResponseStatusCodeSame(201);

        $updated = $this->jsonPatch(
            "/api/groups/{$group['id']}/bills/{$bill['id']}",
            [
                'description'          => 'Hotel (updated)',
                'amountCents'          => 60000,
                'paidByParticipantId'  => $bobId,
                'date'                 => '2026-01-20T00:00:00+00:00',
                'splitType'            => 'equal',
                'participantIds'       => [$aliceId, $bobId],
            ],
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Hotel (updated)', $updated['description']);
        $this->assertSame(60000, $updated['amountCents']);
        $this->assertSame($bobId, $updated['paidBy']['id']);
        $this->assertCount(2, $updated['shares']);
        $this->assertSame(60000, array_sum(array_column($updated['shares'], 'amountCents')));
    }

    public function testItRejectsParticipantFromDifferentGroup(): void
    {
        [$group1, $p1] = $this->createGroupWithParticipants('Group 1', ['Alice']);
        [, $p2] = $this->createGroupWithParticipants('Group 2', ['Bob']);

        $payload = [
            'description' => 'Bill',
            'amountCents' => 1000,
            'paidByParticipantId' => $p1['Alice']['id'],
            'date' => '2026-01-16T00:00:00+00:00',
            'splitType' => 'equal',
            'participantIds' => [$p1['Alice']['id'], $p2['Bob']['id']],
        ];

        $this->json('POST', "/api/groups/{$group1['id']}/bills", $payload);

        $this->assertResponseStatusCodeSame(422);
    }
}
