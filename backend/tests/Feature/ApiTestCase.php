<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $conn = $em->getConnection();
        $conn->executeStatement('DELETE FROM bill_share');
        $conn->executeStatement('DELETE FROM bill');
        $conn->executeStatement('DELETE FROM participant');
        $conn->executeStatement('DELETE FROM "group"');
        $em->clear();
    }

    /** @return array<string, mixed> */
    protected function json(string $method, string $uri, mixed $body = null): array
    {
        $this->client->request(
            $method,
            $uri,
            content: null !== $body ? (string) json_encode($body) : null,
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }

    /** @return array<string, mixed> */
    protected function jsonPatch(string $uri, mixed $body): array
    {
        $this->client->request(
            'PATCH',
            $uri,
            content: (string) json_encode($body),
            server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }

    /**
     * @param list<string> $participantNames
     *
     * @return array{0: array<string, mixed>, 1: array<string, array<string, mixed>>}
     */
    protected function createGroupWithParticipants(string $groupName, array $participantNames): array
    {
        $group = $this->json('POST', '/api/groups', ['name' => $groupName]);

        $participants = [];
        foreach ($participantNames as $name) {
            $participants[$name] = $this->json('POST', "/api/groups/{$group['id']}/participants", ['name' => $name]);
        }

        return [$group, $participants];
    }
}
