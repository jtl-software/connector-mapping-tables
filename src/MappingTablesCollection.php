<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2016 JTL-Software GmbH
 */
namespace jtl\Connector\MappingTables;

use jtl\Connector\CDBC\TablesCollection;

class MappingTablesCollection
{
    protected $strictMode = true;

    /**
     * @var DummyTable
     */
    protected $dummyTable;

    /**
     * @var TablesCollection|AbstractMappingTable[]
     */
    protected $collection;

    /**
     * MappingTableCollection constructor.
     * @param MappingTableInterface[] $tables
     * @param bool $strictMode
     */
    public function __construct(array $tables = [], $strictMode = true)
    {
        $this->collection = new TablesCollection();

        foreach($tables as $table) {
            $this->set($table);
        }

        $this->strictMode = (bool)$strictMode;
    }

    /**
     * @param MappingTableInterface $table
     * @return MappingTablesCollection
     */
    public function set(MappingTableInterface $table)
    {
        $this->collection->set($table);
        return $this;
    }

    /**
     * @param MappingTableInterface $table
     * @return boolean
     */
    public function removeByInstance(MappingTableInterface $table)
    {
        return $this->collection->removeByInstance($table);
    }

    /**
     * @param integer $type
     * @return boolean
     */
    public function removeByType($type)
    {
        if($this->has($type)) {
            $table = $this->findByType($type);
            return $this->collection->removeByInstance($table);
        }
        return false;
    }

    /**
     * @param integer $type
     * @return boolean
     */
    public function has($type)
    {
        return $this->findByType($type) instanceof MappingTableInterface;
    }

    /**
     * @param integer $type
     * @return MappingTableInterface
     * @throws RuntimeException
     */
    public function get($type)
    {
        if($this->has($type)) {
            return $this->findByType($type);
        }

        if(!$this->strictMode) {
            return $this->getDummyTable()->setType($type);
        }

        throw RuntimeException::tableTypeNotFound($type);
    }

    /**
     * @return MappingTableInterface[]
     */
    public function toArray()
    {
        return $this->collection->toArray();
    }

    /**
     * @return boolean
     */
    public function isStrictMode()
    {
        return $this->strictMode;
    }

    /**
     * @param bool $strictMode
     * @return DummyTable
     */
    public function setStrictMode($strictMode)
    {
        $this->strictMode = (bool)$strictMode;
        return $this;
    }

    /**
     * @return DummyTable
     */
    protected function getDummyTable()
    {
        if(!$this->dummyTable instanceof DummyTable) {
            $this->dummyTable = new DummyTable();
        }
        return $this->dummyTable;
    }

    /**
     * @param integer $type
     * @return AbstractMappingTable|null
     */
    protected function findByType($type)
    {
        $result = array_filter($this->collection->toArray(), function(AbstractMappingTable $table) use ($type) {
            return $table->getType() === $type;
        });

        if(count($result) > 0) {
            return reset($result);
        }

        return null;
    }
}