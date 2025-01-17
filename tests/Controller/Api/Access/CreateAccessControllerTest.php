<?php

namespace App\Tests\Controller\Api\Access;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateAccessControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/access/create/access');

        self::assertResponseIsSuccessful();
    }
}
