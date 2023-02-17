<?php

namespace App\Tests\Function;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Product;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

class ProductTest extends ApiTestCase
{
    use RefreshDatabaseTrait;

    private string $path = '/api/products';

    protected Client $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->getKernelBrowser()->catchExceptions(false);
    }

    /**
     * @dataProvider provideGetCollectionTestData
     */
    public function testGetCollection(string $query, int $expectedTotalItems): void
    {
        $this->client->request('GET', $this->path.$query);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/Product',
            '@id' => '/api/products',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => $expectedTotalItems,
        ]);
    }

    public function provideGetCollectionTestData(): array
    {
        return [
            [
                '',
                100,
            ],
            [
                '?stock=true',
                90,
            ],
            [
                '?stock=false',
                10,
            ],
            [
                '?stock=5',
                50,
            ],
        ];
    }

    /**
     * @dataProvider provideProductTestData
     */
    public function testCreateProduct(string $name, int $quantity): void
    {
        $this->client->disableReboot();
        $response = $this->client->request('POST', $this->path, [
            'json' => [
                'name' => $name,
                'quantity' => $quantity,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Product',
            '@type' => 'Product',
            'name' => $name,
            'quantity' => $quantity,
        ]);
        $this->assertMatchesRegularExpression('~^/api\/products\/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Product::class);

        $id = $response->toArray()['id'];
        $response = $this->client->request('GET', $this->path.'/'.$id);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/Product',
            '@type' => 'Product',
            'name' => $name,
            'quantity' => $quantity,
        ]);
    }

    public function provideProductTestData(): array
    {
        return [
            [
                'Product name',
                12,
            ],
        ];
    }

    public function testGetProduct(): void
    {
        $this->client->request('GET', $this->getIri());
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/Product',
            '@type' => 'Product',
            'name' => 'Dummy product name',
            'quantity' => 100,
        ]);
    }

    /**
     * @dataProvider provideProductTestData
     */
    public function testUpdateProduct(string $name, int $quantity): void
    {
        $this->client->request('PUT', $this->getIri(), [
            'json' => [
                'name' => $name,
                'quantity' => $quantity,
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/Product',
            '@type' => 'Product',
            'name' => $name,
            'quantity' => $quantity,
        ]);
    }

    public function testDeleteProduct(): void
    {
        $this->client->request('DELETE', $this->getIri());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Product::class)->findOneBy(['name' => 'Dummy product name'])
        );
    }

    private function getIri(): string
    {
        // Product with name 'Dummy product name' has been generated by Alice when loading test fixtures.
        return $this->findIriBy(Product::class, ['name' => 'Dummy product name']);
    }
}
