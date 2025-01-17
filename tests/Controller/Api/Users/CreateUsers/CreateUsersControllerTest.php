<?php

namespace App\Tests\Controller\Api\Users\CreateUsers;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateUsersControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/create/users/create/users');

        self::assertResponseIsSuccessful();
    }
}
