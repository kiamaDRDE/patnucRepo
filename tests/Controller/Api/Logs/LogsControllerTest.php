<?php

namespace App\Tests\Controller\Api\Logs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LogsControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/logs/logs');

        self::assertResponseIsSuccessful();
    }
}
