<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;
use jtl\Connector\Mapper\IPrimaryKeyMapper;

class MappingTablesManager implements IPrimaryKeyMapper
{
    /**
     * @var MappingTablesCollection
     */
    protected $collection;

    /**
     * MappingTablesManager constructor.
     * @param MappingTableInterface[] $mappingTables
     * @param boolean $strictMode
     */
    public function __construct(array $mappingTables = [], bool $strictMode = true)
    {
        $this->collection = new MappingTablesCollection($mappingTables, $strictMode);
    }

    /**
     * @param integer $type
     * @return MappingTableInterface
     */
    public function getTable(int $type): MappingTableInterface
    {
        return $this->collection->get($type);
    }

    /**
     * @param string $endpointId
     * @param integer $type
     * @return integer|null
     */
    public function getHostId($endpointId, $type): ?int
    {
        return $this->collection->get($type)->getHostId($endpointId);
    }

    /**
     * @param integer $hostId
     * @param integer $type
     * @param null $relationType
     * @return string
     */
    public function getEndpointId($hostId, $type, $relationType = null): ?string
    {
        return $this->collection->get($type)->getEndpoint($hostId, $relationType);
    }

    /**
     * @param string $endpointId
     * @param int $hostId
     * @param int $type
     * @return boolean
     */
    public function save($endpointId, $hostId, $type): bool
    {
        return $this->collection->get($type)->save($endpointId, $hostId);
    }

    /**
     * @param string|null $endpointId
     * @param integer|null $hostId
     * @param integer $type
     * @return boolean
     */
    public function delete($endpointId = null, $hostId = null, $type): bool
    {
        return $this->collection->get($type)->remove($endpointId, $hostId);
    }

    /**
     * @param integer $type
     * @return string[]
     */
    public function findAllEndpoints($type): array
    {
        return $this->collection->get($type)->findEndpoints();
    }

    /**
     * @param string[] $endpoints
     * @param string $type
     * @return string[]
     */
    public function findNotFetchedEndpoints(array $endpoints, string $type): array
    {
        return $this->collection->get($type)->findNotFetchedEndpoints($endpoints);
    }

    /**
     * @param integer $type
     * @return integer
     */
    public function count($type): int
    {
        return $this->collection->get($type)->count();
    }

    /**
     * @return boolean
     */
    public function clear(): bool
    {
        $return = true;
        foreach($this->collection->toArray() as $table) {
            $return = $table->clear() > 0 && $return;
        }
        return $return;
    }

    /**
     * @return boolean
     */
    public function gc(): bool
    {
        return true;
    }

    /**
     * @param MappingTableInterface $table
     * @return MappingTablesManager
     */
    public function setMappingTable(MappingTableInterface $table): MappingTablesManager
    {
        $this->collection->set($table);
        return $this;
    }

    /**
     * @param MappingTableInterface $tables[]
     * @return MappingTablesManager
     */
    public function setMappingTables(array $tables): MappingTablesManager
    {
        foreach($tables as $table) {
            $this->setMappingTable($table);
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isStrictMode(): bool
    {
        return $this->collection->isStrictMode();
    }

    /**
     * @param bool $strictMode
     * @return MappingTablesManager
     */
    public function setStrictMode($strictMode): MappingTablesManager
    {
        $this->collection->setStrictMode($strictMode);
        return $this;
    }
}
