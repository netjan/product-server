<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class StockFilter extends AbstractFilter
{
    private const PROPERTY_NAME = 'stock';
    private const AVAILABLE_VALUES = ['true', 'false', '5'];

    /**
     * @param mixed $value
     */
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        if (self::PROPERTY_NAME !== $property) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            return;
        }
        $value = (string) $value;
        if (!in_array($value, self::AVAILABLE_VALUES)) {
            return;
        }
        if (empty($this->properties['columnName'])) {
            return;
        }
        $columnName = (string) $this->properties['columnName'];
        $parameterName = $queryNameGenerator->generateParameterName($property);
        $rootAlias = $queryBuilder->getRootAliases()[0];
        if ('true' === $value) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s > 0', $rootAlias, $columnName));

            return;
        } elseif ('false' === $value) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s = 0', $rootAlias, $columnName));

            return;
        } elseif ('5' === $value) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s > :%s', $rootAlias, $columnName, $parameterName))
                ->setParameter($parameterName, $value);

            return;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [
            self::PROPERTY_NAME => [
                'property' => self::PROPERTY_NAME,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => sprintf('Available value: <i>(empty)</i>, %s.', implode(', ', self::AVAILABLE_VALUES)),
                'openapi' => [
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ],
        ];

        return $description;
    }
}
