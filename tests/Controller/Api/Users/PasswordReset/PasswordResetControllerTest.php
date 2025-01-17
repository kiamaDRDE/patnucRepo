<?php

namespace App\Tests\Controller\Api\Users\PasswordReset;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PasswordResetControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/password/reset/password/reset');

        self::assertResponseIsSuccessful();
    }
}
