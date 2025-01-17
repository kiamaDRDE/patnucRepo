<?php

namespace App\Tests\Controller\Api\Users\Login;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/login/login');

        self::assertResponseIsSuccessful();
    }
}
