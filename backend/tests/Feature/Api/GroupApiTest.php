<?php

declare(strict_types=1);

namespace App\Tests\Feature\Api;

use App\Tests\Feature\ApiTestCase;

class GroupApiTest extends ApiTestCase
{
    public function test_it_creates_a_group(): void
    {
        $data = $this->json('POST', '/api/groups', ['name' => 'Road Trip']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Road Trip', $data['name']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertSame(0, $data['participantCount']);
    }

    public function test_it_returns_404_for_unknown_group(): void
    {
        $this->json('GET', '/api/groups/01962222-0000-7000-8000-000000000000');

        $this->assertResponseStatusCodeSame(404);
    }

    public function test_it_returns_validation_error_when_name_is_blank(): void
    {
        $this->json('POST', '/api/groups', ['name' => '']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function test_it_lists_all_groups(): void
    {
        $this->json('POST', '/api/groups', ['name' => 'Group A']);
        $this->json('POST', '/api/groups', ['name' => 'Group B']);

        $data = $this->json('GET', '/api/groups');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    public function test_it_patches_a_group_name(): void
    {
        $created = $this->json('POST', '/api/groups', ['name' => 'Old Name']);
        $updated = $this->jsonPatch('/api/groups/'.$created['id'], ['name' => 'New Name']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('New Name', $updated['name']);
    }
}