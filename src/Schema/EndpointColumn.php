<?php

namespace Jtl\Connector\MappingTables\Schema;

use Doctrine\DBAL\Schema\Column;

class EndpointColumn
{
    /**
     * @var Column
     */
    protected $column;

    /**
     * @var boolean
     */
    protected $isPrimary = true;

    /**
     * EndpointColumn constructor.
     * @param Column $column
     * @param bool $primary
     */
    public function __construct(Column $column, bool $primary = true)
    {
        $this->column = $column;
        $this->isPrimary = $primary;
    }

    /**
     * @return Column
     */
    public function getColumn(): Column
    {
        return $this->column;
    }

    /**
     * @return boolean
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * @param Column $column
     * @param bool $primary
     * @return EndpointColumn
     */
    public static function create(Column $column, bool $primary = true): EndpointColumn
    {
        return new self($column, $primary);
    }
}
