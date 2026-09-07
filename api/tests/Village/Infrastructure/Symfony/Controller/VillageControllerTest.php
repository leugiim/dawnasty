<?php

declare(strict_types=1);

namespace App\Tests\Village\Infrastructure\Symfony\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests e2e: pegan sobre las rutas HTTP reales (POST /api/villages,
 * GET /api/villages/{id}) a través del kernel de test, con Doctrine
 * escribiendo en la base de datos de test (when@test de
 * config/packages/doctrine.yaml). Cada test genera su propio id v7
 * aleatorio para no chocar con villages de otras ejecuciones.
 */
final class VillageControllerTest extends WebTestCase
{
    public function test_founding_a_village_and_then_reading_it_back(): void
    {
        $client = static::createClient();
        $id = self::randomUuidV7();

        $client->request(
            'POST',
            '/api/villages',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['id' => $id], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $client->getResponse()->getContent());

        $client->request('GET', '/api/villages/'.$id);
        self::assertResponseIsSuccessful();

        $data = self::decodeData($client->getResponse()->getContent());

        self::assertSame($id, $data['id']);
        self::assertSame('aldea', $data['stage']);
        self::assertSame('Choza del Fundador', $data['mainBuildingName']);
        self::assertSame(1, $data['mainBuildingLevel']);
        self::assertSame(['main_building' => 1], $data['buildings']);
        self::assertSame(['wood' => 0, 'stone' => 0, 'food' => 0, 'ore' => 0], $data['resources']);
        self::assertSame(
            ['total' => 0, 'unemployed' => 0, 'housingCapacity' => 10],
            $data['population'],
        );
        self::assertArrayHasKey('lastCalculatedAt', $data);
    }

    public function test_getting_an_unknown_village_returns_404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/villages/'.self::randomUuidV7());

        self::assertResponseStatusCodeSame(404);
        $body = self::decodeData($client->getResponse()->getContent(), wrapped: false);
        self::assertStringContainsString('not found', $body['message']);
        self::assertNull($body['errors']);
    }

    public function test_getting_with_an_id_that_is_not_a_valid_uuid_v7_returns_400(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/villages/not-a-uuid');

        self::assertResponseStatusCodeSame(400);
    }

    public function test_founding_without_an_id_returns_400(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/villages',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeData(string $json, bool $wrapped = true): array
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return $wrapped ? $decoded['data'] : $decoded;
    }

    private static function randomUuidV7(): string
    {
        $bytes = random_bytes(16);

        $unixTsMs = (int) (microtime(true) * 1000);
        $timeBytes = substr(pack('J', $unixTsMs), 2, 6); // 6 bytes bajos de un uint64 big-endian

        $bytes = $timeBytes.substr($bytes, 6);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70); // nibble de versión = 7
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // nibble de variante = 8-b

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
