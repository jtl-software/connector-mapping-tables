<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

class TableCollection
{
    /**
     * @var boolean
     */
    protected bool $strictMode = false;

    /**
     * @var TableDummy
     */
    protected TableDummy $tableDummy;

    /**
     * @var array<TableInterface>
     */
    protected array $tables = [];

    /**
     * TableCollection constructor.
     * @param TableInterface ...$tables
     */
    public function __construct(TableInterface ...$tables)
    {
        foreach ($tables as $table) {
            $this->set($table);
        }
    }

    /**
     * @param TableInterface $table
     * @return TableCollection
     */
    public function set(TableInterface $table): self
    {
        foreach ($table->getTypes() as $type) {
            $this->tables[$type] = $table;
        }

        return $this;
    }

    /**
     * @param TableInterface $table
     */
    public function removeByInstance(TableInterface $table): void
    {
        $this->tables = \array_filter($this->tables, function (TableInterface $collectedTable) use ($table) {
            return $collectedTable !== $table;
        });
    }

    /**
     * @param integer $type
     */
    public function removeByType(int $type): void
    {
        if ($this->has($type)) {
            unset($this->tables[$type]);
        }
    }

    /**
     * @param integer $type
     * @return boolean
     */
    public function has(int $type): bool
    {
        return isset($this->tables[$type]);
    }

    /**
     * @param integer $type
     * @return TableInterface
     * @throws MappingTablesException
     */
    public function get(int $type): TableInterface
    {
        if ($this->has($type)) {
            return $this->tables[$type];
        }

        if (!$this->strictMode) {
            return $this->getTableDummy($type);
        }

        throw MappingTablesException::tableForTypeNotFound($type);
    }

    /**
     * @return array<TableInterface>
     */
    public function toArray(): array
    {
        $tables = [];
        foreach ($this->tables as $table) {
            if (!\in_array($table, $tables, true)) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * @return boolean
     */
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * @param boolean $strictMode
     * @return TableCollection
     */
    public function setStrictMode(bool $strictMode): self
    {
        $this->strictMode = $strictMode;
        return $this;
    }

    /**
     * @param integer $type
     * @return TableDummy
     */
    protected function getTableDummy(int $type): TableDummy
    {
        if (!isset($this->tableDummy)) {
            $this->tableDummy = new TableDummy();
        }

        $this->tableDummy->setType($type);

        return $this->tableDummy;
    }
}
