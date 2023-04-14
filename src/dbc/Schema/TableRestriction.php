<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Schema;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;

class TableRestriction
{
    /**
     * @var Table
     */
    protected Table $table;

    /**
     * @var string
     */
    protected string $columnName;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * TableRestriction constructor.
     *
     * @param Table  $table
     * @param string $columnName
     * @param mixed  $columnValue
     *
     * @throws SchemaException
     */
    public function __construct(Table $table, string $columnName, $columnValue)
    {
        if (!$table->hasColumn($columnName)) {
            throw SchemaException::columnDoesNotExist($columnName, $table->getName());
        }

        $this->table      = $table;
        $this->columnName = $columnName;
        $this->value      = $columnValue;
    }

    /**
     * @param Table  $table
     * @param string $columnName
     * @param mixed  $columnValue
     *
     * @return TableRestriction
     * @throws SchemaException
     */
    public static function create(Table $table, string $columnName, $columnValue): TableRestriction
    {
        return new self($table, $columnName, $columnValue);
    }

    /**
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * @return mixed
     */
    public function getColumnValue()
    {
        return $this->value;
    }
}
