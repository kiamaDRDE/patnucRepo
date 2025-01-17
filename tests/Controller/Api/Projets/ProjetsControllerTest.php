<?php

namespace App\Tests\Controller\Api\Projets;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProjetsControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/projets/projets');

        self::assertResponseIsSuccessful();
    }
}
