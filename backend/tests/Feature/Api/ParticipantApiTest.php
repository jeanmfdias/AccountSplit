<?php

declare(strict_types=1);

namespace App\Tests\Feature\Api;

use App\Tests\Feature\ApiTestCase;

class ParticipantApiTest extends ApiTestCase
{
    public function testItCreatesAParticipantInAGroup(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Trip']);

        $participant = $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Alice']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Alice', $participant['name']);
        $this->assertArrayHasKey('id', $participant);
        $this->assertSame($group['id'], $participant['groupId']);
    }

    public function testItListsParticipantsOfAGroup(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Trip']);
        $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Alice']);
        $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Bob']);

        $data = $this->json('GET', "/api/groups/{$group['id']}/participants");

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $data);
    }

    public function testItReturns422WhenNameIsBlank(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Trip']);

        $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => '']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testItReturns404ForUnknownGroup(): void
    {
        $this->json('POST', '/api/groups/01962222-0000-7000-8000-000000000000/participants', ['name' => 'Alice']);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testItPatchesAParticipantName(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Trip']);
        $participant = $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Alice']);

        $updated = $this->jsonPatch("/api/groups/{$group['id']}/participants/{$participant['id']}", ['name' => 'Alicia']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Alicia', $updated['name']);
    }

    public function testItDeletesAParticipant(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Trip']);
        $participant = $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Alice']);

        $this->json('DELETE', "/api/groups/{$group['id']}/participants/{$participant['id']}");
        $this->assertResponseStatusCodeSame(204);

        $this->json('GET', "/api/groups/{$group['id']}/participants/{$participant['id']}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testItCannotAccessParticipantViaWrongGroup(): void
    {
        [$group1, $participants1] = $this->createGroupWithParticipants('Group 1', ['Alice']);
        [$group2] = $this->createGroupWithParticipants('Group 2', ['Bob']);

        $this->json('GET', "/api/groups/{$group2['id']}/participants/{$participants1['Alice']['id']}");

        $this->assertResponseStatusCodeSame(404);
    }
}
