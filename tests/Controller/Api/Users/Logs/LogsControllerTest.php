<?php

namespace App\Tests\Controller\Api\Users\Logs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LogsControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/logs/logs');

        self::assertResponseIsSuccessful();
    }
}
