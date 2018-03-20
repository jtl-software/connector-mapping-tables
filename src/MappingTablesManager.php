<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace jtl\Connector\MappingTables;
use jtl\Connector\Mapper\IPrimaryKeyMapper;

class MappingTablesManager implements IPrimaryKeyMapper
{
    /**
     * @var MappingTablesCollection
     */
    protected $collection;

    /**
     * MappingTablesManager constructor.
     * @param MappingTablesCollection|MappingTableInterface[] $mappingTables
     */
    public function __construct($mappingTables = [])
    {
        if(is_array($mappingTables)) {
            $mappingTables = new MappingTablesCollection($mappingTables);
        }

        $this->collection = $mappingTables;
    }

    /**
     * @param mixed $type
     * @return MappingTableInterface
     */
    public function getTable($type)
    {
        return $this->collection->get($type);
    }

    /**
     * @param string $endpointId
     * @param integer $type
     * @return integer|null
     */
    public function getHostId($endpointId, $type)
    {
        return $this->collection->get($type)->getHostId($endpointId);
    }

    /**
     * @param integer $hostId
     * @param integer $type
     * @param null $relationType
     * @return string
     */
    public function getEndpointId($hostId, $type, $relationType = null)
    {
        return $this->collection->get($type)->getEndpointId($hostId);
    }

    /**
     * @param string $endpointId
     * @param int $hostId
     * @param int $type
     * @return boolean
     */
    public function save($endpointId, $hostId, $type)
    {
        return $this->collection->get($type)->save($endpointId, $hostId);
    }

    /**
     * @param string|null $endpointId
     * @param integer|null $hostId
     * @param integer $type
     * @return boolean
     */
    public function delete($endpointId = null, $hostId = null, $type)
    {
        return $this->collection->get($type)->remove($endpointId, $hostId);
    }

    /**
     * @param integer $type
     * @return string[]
     */
    public function findAllEndpoints($type)
    {
        return $this->collection->get($type)->findAllEndpoints();
    }

    /**
     * @param string[] $endpoints
     * @param string $type
     * @return string[]
     */
    public function findNotFetchedEndpoints(array $endpoints, $type)
    {
        return $this->collection->get($type)->findNotFetchedEndpoints($endpoints);
    }

    /**
     * @param integer $type
     * @return integer
     */
    public function count($type)
    {
        return $this->collection->get($type)->count();
    }

    /**
     * @return boolean
     */
    public function clear()
    {
        $return = true;
        foreach($this->collection->toArray() as $table) {
            $return = $table->clear() && $return;
        }
        return $return;
    }

    /**
     * @return boolean
     */
    public function gc()
    {
        return true;
    }

    /**
     * @param MappingTableInterface $table
     * @return MappingTablesManager
     */
    public function setMappingTable(MappingTableInterface $table)
    {
        $this->collection->set($table);
        return $this;
    }

    /**
     * @param MappingTableInterface $tables[]
     * @return MappingTablesManager
     */
    public function setMappingTables(array $tables)
    {
        foreach($tables as $table) {
            $this->setMappingTable($table);
        }
        return $this;
    }
}