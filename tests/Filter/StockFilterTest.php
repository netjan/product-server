<?php

namespace App\Tests\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use App\Entity\Product;
use App\Filter\StockFilter;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StockFilterTest extends KernelTestCase
{
    protected string $filterClass = StockFilter::class;

    protected ManagerRegistry $managerRegistry;

    protected EntityRepository $repository;

    protected string $resourceClass = Product::class;

    protected string $alias = 'o';

    protected function setUp(): void
    {
        self::bootKernel();

        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository = $this->managerRegistry->getManagerForClass($this->resourceClass)->getRepository($this->resourceClass);
    }

    /**
     * @dataProvider provideApplyTestData
     */
    public function testApply(
        ?array $properties,
        array $filterParameters,
        string $expectedDql,
        array $expectedParameters = null,
        string $resourceClass = null
    ): void {
        $filter = $this->buildFilter($properties);
        $queryBuilder = $this->repository->createQueryBuilder($this->alias);
        $filter->apply($queryBuilder, new QueryNameGenerator(), $resourceClass, null, ['filters' => $filterParameters]);

        $this->assertSame($expectedDql, $queryBuilder->getQuery()->getDQL());

        if (null === $expectedParameters) {
            return;
        }

        foreach ($expectedParameters as $parameterName => $expectedParameterValue) {
            $queryParameter = $queryBuilder->getQuery()->getParameter($parameterName);

            $this->assertNotNull($queryParameter, sprintf('Expected query parameter "%s" to be set', $parameterName));
            $this->assertEquals($expectedParameterValue, $queryParameter->getValue(), sprintf('Expected query parameter "%s" to be "%s"', $parameterName, var_export($expectedParameterValue, true)));
        }
    }

    public function provideApplyTestData(): array
    {
        return [
            'string ("")' => [
                ['columnName' => 'quantity',],
                [],
                'SELECT o FROM App\Entity\Product o',
                null,
                $this->alias,
            ],
            'string ("true")' => [
                ['columnName' => 'quantity',],
                [
                    'stock' => 'true',
                ],
                'SELECT o FROM App\Entity\Product o WHERE o.quantity > 0',
                null,
                $this->alias,
            ],
            'string ("false")' => [
                ['columnName' => 'quantity',],
                [
                    'stock' => 'false',
                ],
                'SELECT o FROM App\Entity\Product o WHERE o.quantity = 0',
                null,
                $this->alias,
            ],
            'string ("5")' => [
                ['columnName' => 'quantity',],
                [
                    'stock' => '5',
                ],
                'SELECT o FROM App\Entity\Product o WHERE o.quantity > :stock_p1',
                [
                    'stock_p1' => '5',
                ],
                $this->alias,
            ],
            'wrong property name' => [
                ['columnName' => 'quantity',],
                [
                    'wrong_property_name' => '5',
                ],
                'SELECT o FROM App\Entity\Product o',
                null,
                $this->alias,
            ],
            'value not string' => [
                ['columnName' => 'quantity',],
                [
                    'stock' => [],
                ],
                'SELECT o FROM App\Entity\Product o',
                null,
                $this->alias,
            ],
            'value not valid' => [
                ['columnName' => 'quantity',],
                [
                    'stock' => 'not valid value',
                ],
                'SELECT o FROM App\Entity\Product o',
                null,
                $this->alias,
            ],
            'empty columnName' => [
                ['columnName' => '',],
                [
                    'stock' => 'true',
                ],
                'SELECT o FROM App\Entity\Product o',
                null,
                $this->alias,
            ],
        ];
    }

    public function testGetDescription(): void
    {
        $filter = $this->buildFilter([
            'columnName' => 'quantity',
        ]);
        $this->assertEquals([
            'stock' => [
                'property' => 'stock',
                'type' => 'string',
                'required' => false,
                'description' => sprintf('Available value: <i>(empty)</i>, %s.', implode(', ', ['true', 'false', '5'])),
                'openapi' => [
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields(): void
    {
        $filter = $this->buildFilter();
        $this->assertEquals([
            'stock' => [
                'property' => 'stock',
                'type' => 'string',
                'required' => false,
                'description' => sprintf('Available value: <i>(empty)</i>, %s.', implode(', ', ['true', 'false', '5'])),
                'openapi' => [
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    protected function buildFilter(?array $properties = null): FilterInterface
    {
        return new $this->filterClass($this->managerRegistry, null, $properties);
    }
}
