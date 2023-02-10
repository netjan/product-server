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
        if (
            !$this->isPropertyEnabled($property, $resourceClass)
        ) {
            return;
        }
        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            return;
        }
        $value = (string) $value;
        if (!in_array($value, ['true', 'false', '5'])) {
            return;
        }
        $columnName = 'amount';
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
        if (!$this->properties) {
            return [];
        }

        $description = [];
        /**
         * @var null $_strategy unused value
         */
        foreach ($this->properties as $property => $_strategy) {
            $description[$property] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Filter amount. Available value: true, false, 5. Empty value is allowed.',
                'openapi' => [
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ];
        }

        return $description;
    }
}
