<?php

namespace App\Tests\Controller\Api\Users\UpdateUsers;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UpdateUsersControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/update/users/update/users');

        self::assertResponseIsSuccessful();
    }
}
