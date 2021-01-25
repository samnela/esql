<?php

/*
 * This file is part of the ESQL project.
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\Bridge\ApiPlatform\Extension\QueryCollectionExtensionInterface;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Category;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CategoryFilterExtension implements QueryCollectionExtensionInterface
{
    private RequestStack $requestStack;
    private ESQLInterface $esql;
    private ManagerRegistry $managerRegistry;

    public function __construct(RequestStack $requestStack, ESQLInterface $esql, ManagerRegistry $managerRegistry)
    {
        $this->requestStack = $requestStack;
        $this->esql = $esql;
        $this->managerRegistry = $managerRegistry;
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->query->has('category') || null === $category = $request->query->get('category')) {
            return [$query, $parameters];
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (\is_array($category)) {
            throw new BadRequestHttpException();
        }

        ['column' => $column, 'join' => $join, 'columns' => $columns, 'table' => $table] = $this->esql->__invoke($resourceClass);
        ['columns' => $categoryColumns, 'alias' => $categoryAlias] = $this->esql->__invoke(Category::class);

        $query = <<<SQL
WITH
    descendants(identifier, name, parent_id) AS (
        SELECT c.identifier, c.name, c.parent_id FROM category c WHERE c.identifier = :category
        UNION ALL
        SELECT c.identifier, c.name, c.parent_id FROM descendants, category c WHERE c.parent_id = descendants.identifier
    )
SELECT {$columns()} FROM $table
JOIN descendants {$categoryAlias} ON {$join(Category::class)}
SQL;

        $parameters['category'] = $category;

        return [$query, $parameters];
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return Product::class === $resourceClass;
    }
}
