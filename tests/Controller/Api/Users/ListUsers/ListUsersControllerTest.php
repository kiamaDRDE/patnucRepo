<?php

namespace App\Tests\Controller\Api\Users\ListUsers;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListUsersControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/list/users/list/users');

        self::assertResponseIsSuccessful();
    }
}
