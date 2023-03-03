<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\Dbc\Schema;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;

class TableRestriction
{
    /**
     * @var Table
     */
    protected $table;

    /**
     * @var string
     */
    protected $columnName;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * TableRestriction constructor.
     * @param Table $table
     * @param string $columnName
     * @param mixed $columnValue
     * @throws SchemaException
     */
    public function __construct(Table $table, string $columnName, $columnValue)
    {
        if (!$table->hasColumn($columnName)) {
            throw SchemaException::columnDoesNotExist($columnName, $table->getName());
        }

        $this->table = $table;
        $this->columnName = $columnName;
        $this->value = $columnValue;
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

    /**
     * @param Table $table
     * @param string $columnName
     * @param mixed $columnValue
     * @return TableRestriction
     * @throws SchemaException
     */
    public static function create(Table $table, string $columnName, $columnValue): TableRestriction
    {
        return new static($table, $columnName, $columnValue);
    }
}
