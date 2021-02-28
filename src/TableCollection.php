<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2016 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

use Jtl\Connector\Dbc\TableCollection as DbcTableCollection;

class TableCollection
{
    /**
     * @var boolean
     */
    protected $strictMode = false;

    /**
     * @var TableDummy
     */
    protected $tableDummy;

    /**
     * @var DbcTableCollection
     */
    protected $collection;

    /**
     * TableCollection constructor.
     * @param TableInterface ...$tables
     */
    public function __construct(TableInterface ...$tables)
    {
        $this->collection = new DbcTableCollection();

        foreach ($tables as $table) {
            $this->set($table);
        }
    }

    /**
     * @param TableInterface $table
     * @return TableCollection
     */
    public function set(TableInterface $table): TableCollection
    {
        $this->collection->set($table);
        return $this;
    }

    /**
     * @param TableInterface $table
     * @return bool
     * @throws \Exception
     */
    public function removeByInstance(TableInterface $table): bool
    {
        return $this->collection->removeByInstance($table);
    }

    /**
     * @param integer $type
     * @return boolean
     * @throws \Exception
     */
    public function removeByType(int $type): bool
    {
        if ($this->has($type)) {
            return $this->collection->removeByInstance($this->findByType($type));
        }
        return false;
    }

    /**
     * @param integer $type
     * @return boolean
     */
    public function has(int $type): bool
    {
        return $this->findByType($type) instanceof TableInterface;
    }

    /**
     * @param int $type
     * @return TableInterface
     * @throws MappingTablesException
     */
    public function get(int $type): TableInterface
    {
        if ($this->has($type)) {
            return $this->findByType($type);
        }

        if (!$this->strictMode) {
            return $this->getTableDummy($type);
        }

        throw MappingTablesException::tableForTypeNotFound($type);
    }

    /**
     * @return array<AbstractTable>
     */
    public function toArray(): array
    {
        return $this->collection->toArray();
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
    public function setStrictMode(bool $strictMode): TableCollection
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
        if (!$this->tableDummy instanceof TableDummy) {
            $this->tableDummy = new TableDummy();
        }

        $this->tableDummy->setType($type);

        return $this->tableDummy;
    }

    /**
     * @param int $type
     * @return AbstractTable|null
     */
    protected function findByType(int $type): ?AbstractTable
    {
        $result = array_values(array_filter($this->collection->toArray(), function (AbstractTable $table) use ($type) {
            return in_array($type, $table->getTypes(), true);
        }));

        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }
}
