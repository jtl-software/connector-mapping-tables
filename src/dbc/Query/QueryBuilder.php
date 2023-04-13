<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Query;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryException;
use Jtl\Connector\Dbc\Connection;

class QueryBuilder extends \Doctrine\DBAL\Query\QueryBuilder
{
    /**
     * @var array{empty}|array<string, array<string, mixed>>|array<string, mixed>
     */
    protected array   $tableRestrictions = [];
    protected ?string $fromTable;
    protected ?string $fromAlias;

    /**
     * QueryBuilder constructor.
     *
     * @param Connection                                                            $connection
     * @param array{empty}|array<string, mixed>|array<string, array<string, mixed>> $tableRestrictions
     * @param string|null                                                           $fromTable
     * @param string|null                                                           $fromAlias
     */
    public function __construct(
        Connection $connection,
        array      $tableRestrictions = [],
        string     $fromTable = null,
        string     $fromAlias = null
    ) {
        parent::__construct($connection);
        $this->tableRestrictions = $tableRestrictions;
        $this->fromTable         = $fromTable;
        $this->fromAlias         = $fromAlias;
    }

    /**
     * @return string
     * @throws QueryException
     */
    public function getSQL(): string
    {
        if (!\is_null($this->fromTable)) {
            $this->resetQueryPart('from');
            switch ($this->getType()) {
                case self::SELECT:
                    $this->from($this->fromTable, $this->fromAlias);
                    break;
                case self::INSERT:
                    $this->insert($this->fromTable);
                    break;
                case self::UPDATE:
                    $this->update($this->fromTable, $this->fromAlias);
                    break;
                case self::DELETE:
                    $this->delete($this->fromTable, $this->fromAlias);
                    break;
            }
        }

        /** @var array<array<string, string>|string> $queryPart */
        $queryPart = $this->getQueryPart('from');
        foreach ($queryPart as $table) {
            if (!$table) {
                continue;
            }
            $this->assignTableRestrictions(\is_array($table) ? $table['table'] : $table);
        }
        return parent::getSQL();
    }

    /**
     * @param string $table
     */
    protected function assignTableRestrictions(string $table): void
    {
        if (isset($this->tableRestrictions[$table]) && \is_array($this->tableRestrictions[$table])) {
            foreach ($this->tableRestrictions[$table] as $column => $value) {
                $id    = 'glob_id_' . $column;
                $where = $this->getQueryPart('where');
                $this->setParameter($id, $value);
                $this->setValue($column, ':' . $id);
                $this->set($column, ':' . $id);
                $whereCondition = $column . ' = :' . $id;

                if (
                    !$where instanceof CompositeExpression
                    || $where->getType() !== CompositeExpression::TYPE_AND
                    || !\str_contains((string)$where, $whereCondition)
                ) {
                    $this->andWhere($whereCondition);
                }
            }
        }
    }
}
