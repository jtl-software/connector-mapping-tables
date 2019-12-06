<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;
use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;

class MappingTablesManager implements PrimaryKeyMapperInterface
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
     * @param integer $type
     * @param string $endpointId
     * @return integer|null
     */
    public function getHostId(int $type, string $endpointId): ?int
    {
        return $this->collection->get($type)->getHostId($endpointId);
    }

    /**
     * @param integer $type
     * @param integer $hostId
     * @return string
     */
    public function getEndpointId(int $type, int $hostId): ?string
    {
        return $this->collection->get($type)->getEndpoint($hostId);
    }

    /**
     * @param string $endpointId
     * @param int $hostId
     * @param int $type
     * @return boolean
     */
    public function save(int $type, string $endpointId, int $hostId): bool
    {
        return $this->collection->get($type)->save($endpointId, $hostId);
    }

    /**
     * @param integer $type
     * @param string|null $endpointId
     * @param integer|null $hostId
     * @return boolean
     */
    public function delete(int $type, string $endpointId = null, int $hostId = null): bool
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
     * @param int $type
     * @param string[] $endpoints
     * @return string[]
     */
    public function findNotFetchedEndpoints(int $type, array $endpoints): array
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
     * @param integer|null $type
     * @return boolean
     */
    public function clear(int $type = null): bool
    {
        if(!is_null($type)) {
            return $this->collection->get($type)->clear() >= 0;
        }

        foreach($this->collection->toArray() as $table) {
            $table->clear();
        }

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
     * @param array $tables
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
