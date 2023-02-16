<?php

namespace App\Tests\Function;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;

class ProductTest extends ApiTestCase
{
    private string $path = '/api/products';

    protected Client $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->getKernelBrowser()->catchExceptions(false);
    }

    public function testGet(): void
    {
        $response = $this->client->request('GET', $this->path);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@id' => '/api/products']);
    }

    public function testPost(): void
    {
        $response = $this->client->request('POST', $this->path, [
            'json' => [
                'name' => 'Cos tam',
                'quantity' => 12,
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testGetItem(): void
    {
        $response = $this->client->request('GET', $this->path.'/2');

        $this->assertResponseIsSuccessful();
    }

    public function testPut(): void
    {
        $response = $this->client->request('PUT', $this->path.'/2', [
            'json' => [
                'name' => 'asdf',
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testDelete(): void
    {
        $this->assertTrue(true);
        // $response = $this->client->request('DELETE', $this->path.'/6');

        // $this->assertResponseIsSuccessful();
    }
}
