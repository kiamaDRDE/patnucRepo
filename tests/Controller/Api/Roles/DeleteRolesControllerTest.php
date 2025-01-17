<?php

namespace App\Tests\Controller\Api\Roles;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DeleteRolesControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/roles/delete/roles');

        self::assertResponseIsSuccessful();
    }
}
