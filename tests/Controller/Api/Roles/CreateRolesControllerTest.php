<?php

namespace App\Tests\Controller\Api\Roles;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateRolesControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/roles/create/roles');

        self::assertResponseIsSuccessful();
    }
}
