<?php

declare(strict_types=1);

namespace App\Tests\Feature\Api;

use App\Tests\Feature\ApiTestCase;

class ParticipantApiTest extends ApiTestCase
{
    public function test_it_creates_a_participant_in_a_group(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Trip']);

        $participant = $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Alice']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Alice', $participant['name']);
        $this->assertArrayHasKey('id', $participant);
        $this->assertSame($group['id'], $participant['groupId']);
    }

    public function test_it_lists_participants_of_a_group(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Trip']);
        $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Alice']);
        $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Bob']);

        $data = $this->json('GET', "/api/groups/{$group['id']}/participants");

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $data);
    }

    public function test_it_returns_422_when_name_is_blank(): void
    {
        $group = $this->json('POST', '/api/groups', ['name' => 'Trip']);

        $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => '']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function test_it_returns_404_for_unknown_group(): void
    {
        $this->json('POST', '/api/groups/01962222-0000-7000-8000-000000000000/participants', ['name' => 'Alice']);

        $this->assertResponseStatusCodeSame(404);
    }

    public function test_it_patches_a_participant_name(): void
    {
        $group       = $this->json('POST', '/api/groups', ['name' => 'Trip']);
        $participant = $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Alice']);

        $updated = $this->jsonPatch("/api/groups/{$group['id']}/participants/{$participant['id']}", ['name' => 'Alicia']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Alicia', $updated['name']);
    }

    public function test_it_deletes_a_participant(): void
    {
        $group       = $this->json('POST', '/api/groups', ['name' => 'Trip']);
        $participant = $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => 'Alice']);

        $this->json('DELETE', "/api/groups/{$group['id']}/participants/{$participant['id']}");
        $this->assertResponseStatusCodeSame(204);

        $this->json('GET', "/api/groups/{$group['id']}/participants/{$participant['id']}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function test_it_cannot_access_participant_via_wrong_group(): void
    {
        [$group1, $participants1] = $this->createGroupWithParticipants('Group 1', ['Alice']);
        [$group2]                 = $this->createGroupWithParticipants('Group 2', ['Bob']);

        $this->json('GET', "/api/groups/{$group2['id']}/participants/{$participants1['Alice']['id']}");

        $this->assertResponseStatusCodeSame(404);
    }
}