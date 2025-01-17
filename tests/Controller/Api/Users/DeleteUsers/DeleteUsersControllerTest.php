<?php

namespace App\Tests\Controller\Api\Users\DeleteUsers;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DeleteUsersControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/delete/users/delete/users');

        self::assertResponseIsSuccessful();
    }
}
