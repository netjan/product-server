<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class ProductTest extends TestCase
{
    private Product $entityTest;

    public function setUp(): void
    {
        $this->entityTest = new Product();
    }

    public function testCreateEmptyProduct(): void
    {
        $this->assertInstanceOf(Product::class, $this->entityTest);
        $this->assertNull($this->entityTest->getId());
        $this->assertNull($this->entityTest->getName());
        $this->assertNull($this->entityTest->getQuantity());
    }

    public function propertyGetSet(): \Generator
    {
        yield ['name', 'StringValue'];
        yield ['quantity', 1];
    }

    /**
     * @dataProvider propertyGetSet
     */
    public function testGetSet(string $propertyName, $expectedValue): void
    {
        $setMethod = 'set'.\ucfirst($propertyName);
        $this->entityTest->$setMethod($expectedValue);
        $getMethod = 'get'.\ucfirst($propertyName);
        $actual = $this->entityTest->$getMethod();
        $this->assertSame($expectedValue, $actual);
        $this->assertEquals($expectedValue, $actual);
    }

    public function attributesAssertProvider(): array
    {
        return [
            ['id', ORM\Id::class, []],
            ['id', ORM\GeneratedValue::class, []],
            ['id', ORM\Column::class, []],
            ['name', ORM\Column::class, ['length' => 255]],
            ['quantity', ORM\Column::class, []],
            ['quantity', Assert\GreaterThanOrEqual::class, [0]],
        ];
    }

    /**
     * @dataProvider attributesAssertProvider
     */
    public function testAssertAttributesSetOnProperty(string $propertyName, string $expectedAttributeName, array $expectedArguments): void
    {
        $property = new \ReflectionProperty(Product::class, $propertyName);
        $result = $property->getAttributes($expectedAttributeName);

        $this->assertCount(
            1,
            $result,
            sprintf('%s::%s does not contain attribute "%s".', Product::class, $propertyName, $expectedAttributeName)
        );

        $attribute = $result[0];
        $this->assertInstanceOf(
            \ReflectionAttribute::class,
            $attribute
        );

        $this->assertSame(
            $expectedArguments,
            $attribute->getArguments()
        );
    }
}
