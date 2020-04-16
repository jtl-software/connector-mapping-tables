<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2016 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

use Jtl\Connector\Dbc\TableCollection;

class MappingTablesCollection
{
    /**
     * @var boolean
     */
    protected $strictMode = true;

    /**
     * @var DummyTable
     */
    protected $dummyTable;

    /**
     * @var TableCollection|AbstractMappingTable[]
     */
    protected $collection;

    /**
     * MappingTableCollection constructor.
     * @param MappingTableInterface[] $tables
     * @param boolean $strictMode
     */
    public function __construct(array $tables = [], bool $strictMode = true)
    {
        $this->collection = new TableCollection();

        foreach($tables as $table) {
            $this->set($table);
        }

        $this->strictMode = $strictMode;
    }

    /**
     * @param MappingTableInterface $table
     * @return MappingTablesCollection
     */
    public function set(MappingTableInterface $table): MappingTablesCollection
    {
        $this->collection->set($table);
        return $this;
    }

    /**
     * @param MappingTableInterface $table
     * @return boolean
     * @throws \Exception
     */
    public function removeByInstance(MappingTableInterface $table): bool
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
    public function has(int $type): bool
    {
        return $this->findByType($type) instanceof MappingTableInterface;
    }

    /**
     * @param integer $type
     * @return MappingTableInterface
     * @throws RuntimeException
     */
    public function get(int $type): MappingTableInterface
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
     * @return MappingTablesCollection
     */
    public function setStrictMode(bool $strictMode): MappingTablesCollection
    {
        $this->strictMode = $strictMode;
        return $this;
    }

    /**
     * @return DummyTable
     */
    protected function getDummyTable(): DummyTable
    {
        if(!$this->dummyTable instanceof DummyTable) {
            $this->dummyTable = new DummyTable();
        }

        return $this->dummyTable;
    }

    /**
     * @param integer $type
     * @return MappingTableInterface|null
     */
    protected function findByType(int $type): ?MappingTableInterface
    {
        $result = array_filter($this->collection->toArray(), function(MappingTableInterface $table) use ($type) {
            return $table->getType() === $type;
        });

        if(count($result) > 0) {
            return reset($result);
        }

        return null;
    }
}
