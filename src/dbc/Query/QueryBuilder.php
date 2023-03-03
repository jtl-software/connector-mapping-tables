<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\Dbc\Query;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Jtl\Connector\Dbc\Connection;

class QueryBuilder extends \Doctrine\DBAL\Query\QueryBuilder
{
    /**
     * @var mixed[]
     */
    protected $tableRestrictions = [];

    /**
     * @var string|null
     */
    protected $fromTable;

    /**
     * @var string|null
     */
    protected $fromAlias;

    /**
     * QueryBuilder constructor.
     * @param Connection $connection
     * @param mixed[] $tableRestrictions
     * @param string|null $fromTable
     * @param string|null $fromAlias
     */
    public function __construct(
        Connection $connection,
        array $tableRestrictions = [],
        string $fromTable = null,
        string $fromAlias = null
    ) {
        parent::__construct($connection);
        $this->tableRestrictions = $tableRestrictions;
        $this->fromTable = $fromTable;
        $this->fromAlias = $fromAlias;
    }

    /**
     * @return string
     */
    public function getSQL(): string
    {
        if (!is_null($this->fromTable)) {
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

        foreach ($this->getQueryPart('from') as $table) {
            $this->assignTableRestrictions(is_array($table) ? $table['table'] : $table);
        }
        return parent::getSQL();
    }

    /**
     * @param string $table
     */
    protected function assignTableRestrictions($table): void
    {
        if (isset($this->tableRestrictions[$table])) {
            foreach ($this->tableRestrictions[$table] as $column => $value) {
                /** @var CompositeExpression $where */
                $id = 'glob_id_' . $column;
                $where = $this->getQueryPart('where');
                parent::setParameter($id, $value);
                parent::setValue($column, ':' . $id);
                parent::set($column, ':' . $id);
                $whereCondition = $column . ' = :' . $id;
                if (!$where instanceof CompositeExpression || $where->getType() !== CompositeExpression::TYPE_AND || !strstr($where, $whereCondition)) {
                    parent::andWhere($whereCondition);
                }
            }
        }
    }
}
