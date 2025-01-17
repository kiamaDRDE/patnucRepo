<?php

namespace App\Tests\Controller\Api\Roles;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetRolesControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/roles/get/roles');

        self::assertResponseIsSuccessful();
    }
}
