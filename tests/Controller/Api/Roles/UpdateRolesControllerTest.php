<?php

namespace App\Tests\Controller\Api\Roles;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UpdateRolesControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/roles/update/roles');

        self::assertResponseIsSuccessful();
    }
}
