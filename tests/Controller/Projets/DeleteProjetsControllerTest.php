<?php

namespace App\Tests\Controller\Projets;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DeleteProjetsControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/projets/delete/projets');

        self::assertResponseIsSuccessful();
    }
}
