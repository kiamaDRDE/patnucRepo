<?php

namespace App\Tests\Controller\Api\Access;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetAccessControllerTest extends WebTestCase{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/access/get/access');

        self::assertResponseIsSuccessful();
    }
}
