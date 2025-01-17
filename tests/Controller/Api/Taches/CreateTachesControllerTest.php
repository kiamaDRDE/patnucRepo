<?php

namespace App\Tests\Controller\Api\Taches;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateTachesControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/taches/create/taches');

        self::assertResponseIsSuccessful();
    }
}
