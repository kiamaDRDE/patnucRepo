<?php

namespace App\Tests\Controller\Api\Users\InfoUsers;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InfoUsersControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/info/users/info/users');

        self::assertResponseIsSuccessful();
    }
}
