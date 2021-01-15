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

namespace Soyuka\ESQL\Bridge\ApiPlatform\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\Persistence\ManagerRegistry;
use Jane\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\Bridge\Doctrine\ClassInfoTrait;
use Soyuka\ESQL\ESQLInterface;

final class DataPersister implements DataPersisterInterface, ContextAwareDataPersisterInterface
{
    use ClassInfoTrait;

    private ManagerRegistry $managerRegistry;
    private AutoMapperInterface $automapper;
    private ESQLInterface $esql;

    public function __construct(ManagerRegistry $managerRegistry, AutoMapperInterface $automapper, ESQLInterface $esql)
    {
        $this->managerRegistry = $managerRegistry;
        $this->automapper = $automapper;
        $this->esql = $esql;
    }

    /**
     * @param mixed $data
     */
    public function persist($data, array $context = [])
    {
        ['table' => $Table, 'columns' => $Columns, 'parameters' => $Parameters, 'predicates' => $Predicates, 'identifierPredicate' => $IdentifierPredicate] = $this->esql->__invoke($data);
        $connection = $this->managerRegistry->getConnection();
        $binding = $this->automapper->map($data, 'array');

        if ($context['previous_data'] ?? null) {
            $query = <<<SQL
            UPDATE {$Table()} SET {$Predicates()}
            WHERE {$IdentifierPredicate()}
SQL;
        } else {
            $query = <<<SQL
            INSERT INTO {$Table()} ({$Columns()}) VALUES ({$Parameters($binding)});
SQL;
        }

        $connection->beginTransaction();
        $stmt = $connection->prepare($query);
        $stmt->execute($binding);
        $connection->commit();

        $query = <<<SQL
        SELECT * FROM {$Table()} WHERE {$IdentifierPredicate()}
SQL;
        $stmt = $connection->prepare($query);
        $stmt->execute(['id' => ($context['previous_data'] ?? null) ? $context['previous_data']->getId() : $connection->lastInsertId()]);
        $data = $stmt->fetch();

        /** @var object */
        return $this->automapper->map($data, $this->getObjectClass($data));
    }

    /**
     * @param mixed $data
     */
    public function remove($data, array $context = []): void
    {
        ['table' => $Table, 'identifierPredicate' => $IdentifierPredicate] = $this->esql->__invoke($data);
        $connection = $this->managerRegistry->getConnection();
        $connection->beginTransaction();
        $query = <<<SQL
        DELETE FROM {$Table($data)} WHERE {$IdentifierPredicate($data)}
SQL;
        $stmt = $connection->prepare($query);
        $stmt->execute(['id' => $data->getId()]);
        $connection->commit();
    }

    /**
     * @param mixed $data
     */
    public function supports($data, array $context = []): bool
    {
        return null !== $this->managerRegistry->getManagerForClass($this->getObjectClass($data));
    }
}
