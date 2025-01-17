<?php

namespace App\Tests\Controller\Api\Users;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/user');

        self::assertResponseIsSuccessful();
    }
}
