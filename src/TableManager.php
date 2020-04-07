<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\MappingTables;

use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;

class TableManager implements PrimaryKeyMapperInterface
{
    /**
     * @var TableCollection
     */
    protected $collection;

    /**
     * MappingTablesManager constructor.
     * @param TableInterface ...$tables
     */
    public function __construct(TableInterface ...$tables)
    {
        $this->collection = new TableCollection(...$tables);
    }

    /**
     * @param integer $type
     * @return TableInterface
     */
    public function getTableByType(int $type): TableInterface
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
        return $this->collection->get($type)->getEndpoint($type, $hostId);
    }

    /**
     * @param string $endpointId
     * @param int $hostId
     * @param int $type
     * @return boolean
     */
    public function save(int $type, string $endpointId, int $hostId): bool
    {
        return is_int($this->collection->get($type)->save($endpointId, $hostId));
    }

    /**
     * @param integer $type
     * @param string|null $endpointId
     * @param integer|null $hostId
     * @return boolean
     */
    public function delete(int $type, string $endpointId = null, int $hostId = null): bool
    {
        return is_int($this->collection->get($type)->remove($type, $endpointId, $hostId));
    }

    /**
     * @param integer $type
     * @return string[]
     */
    public function findAllEndpointIds($type): array
    {
        return $this->collection->get($type)->findEndpoints($type);
    }

    /**
     * @param int $type
     * @param string[] $endpoints
     * @return string[]
     */
    public function filterMappedEndpointIds(int $type, array $endpoints): array
    {
        return $this->collection->get($type)->filterMappedEndpoints($endpoints);
    }

    /**
     * @param integer $type
     * @return integer
     */
    public function count(int $type = null): int
    {
        if (!is_null($type)) {
            return $this->collection->get($type)->count($type);
        }

        $count = 0;
        foreach ($this->collection->toArray() as $table) {
            $count += $table->count();
        }
        return $count;
    }

    /**
     * @param integer|null $type
     * @return boolean
     */
    public function clear(int $type = null): bool
    {
        if (!is_null($type)) {
            return is_int($this->collection->get($type)->clear($type));
        }

        foreach ($this->collection->toArray() as $table) {
            $table->clear();
        }

        return true;
    }

    /**
     * @param TableInterface $table
     * @return TableManager
     */
    public function setMappingTable(TableInterface $table): TableManager
    {
        $this->collection->set($table);
        return $this;
    }

    /**
     * @param array $tables
     * @return TableManager
     */
    public function setMappingTables(array $tables): TableManager
    {
        foreach ($tables as $table) {
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
     * @return TableManager
     */
    public function setStrictMode(bool $strictMode): TableManager
    {
        $this->collection->setStrictMode($strictMode);
        return $this;
    }
}
