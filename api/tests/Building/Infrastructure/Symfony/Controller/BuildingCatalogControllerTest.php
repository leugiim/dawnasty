<?php

declare(strict_types=1);

namespace App\Tests\Building\Infrastructure\Symfony\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BuildingCatalogControllerTest extends WebTestCase
{
    public function test_it_returns_the_full_building_catalog(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/buildings/catalog');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['data'];

        self::assertCount(3, $data);
        self::assertEqualsCanonicalizing(
            ['main_building', 'house', 'farm'],
            array_column($data, 'type'),
        );

        $farm = current(array_filter($data, static fn (array $view): bool => $view['type'] === 'farm'));
        self::assertNotFalse($farm);
        self::assertTrue($farm['producesFood']);
        self::assertNotEmpty($farm['levels']);
    }
}
