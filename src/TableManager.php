<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;

class TableManager implements PrimaryKeyMapperInterface
{
    /**
     * @var TableCollection
     */
    protected TableCollection $collection;

    /**
     * MappingTablesManager constructor.
     *
     * @param TableInterface ...$tables
     */
    public function __construct(TableInterface ...$tables)
    {
        $this->collection = new TableCollection(...$tables);
    }

    /**
     * @param integer $type
     *
     * @return TableInterface
     * @throws MappingTablesException
     */
    public function getTableByType(int $type): TableInterface
    {
        return $this->collection->get($type);
    }

    /**
     * @param integer $type
     * @param string  $endpointId
     *
     * @return integer|null
     * @throws MappingTablesException
     */
    public function getHostId(int $type, string $endpointId): ?int
    {
        return $this->collection->get($type)->getHostId($endpointId);
    }

    /**
     * @param integer $type
     * @param integer $hostId
     *
     * @return string
     * @throws MappingTablesException
     */
    public function getEndpointId(int $type, int $hostId): ?string
    {
        return $this->collection->get($type)->getEndpoint($hostId, $type);
    }

    /**
     * @param string $endpointId
     * @param int    $hostId
     * @param int    $type
     *
     * @return boolean
     * @throws MappingTablesException
     */
    public function save(int $type, string $endpointId, int $hostId): bool
    {
        return \is_int($this->collection->get($type)->save($endpointId, $hostId));
    }

    /**
     * @param integer      $type
     * @param string|null  $endpointId
     * @param integer|null $hostId
     *
     * @return boolean
     * @throws MappingTablesException
     */
    public function delete(int $type, string $endpointId = null, int $hostId = null): bool
    {
        return \is_int($this->collection->get($type)->remove($endpointId, $hostId, $type));
    }

    /**
     * @param integer $type
     *
     * @return string[]
     * @throws MappingTablesException
     */
    public function findAllEndpointIds($type): array
    {
        return $this->collection->get($type)->findEndpoints([], [], [], null, null, $type);
    }

    /**
     * @param int      $type
     * @param string[] $endpoints
     *
     * @return string[]
     * @throws MappingTablesException
     */
    public function filterMappedEndpointIds(int $type, array $endpoints): array
    {
        return $this->collection->get($type)->filterMappedEndpoints($endpoints);
    }

    /**
     * @param integer|null $type
     *
     * @return integer
     * @throws MappingTablesException
     */
    public function count(int $type = null): int
    {
        if (!\is_null($type)) {
            return $this->collection->get($type)->count([], [], [], null, null, $type);
        }

        $count = 0;
        foreach ($this->collection->toArray() as $table) {
            $count += $table->count();
        }
        return $count;
    }

    /**
     * @param integer|null $type
     *
     * @return boolean
     * @throws MappingTablesException
     */
    public function clear(int $type = null): bool
    {
        if (!\is_null($type)) {
            return \is_int($this->collection->get($type)->clear($type));
        }

        foreach ($this->collection->toArray() as $table) {
            $table->clear();
        }

        return true;
    }

    /**
     * @param TableInterface[] $tables
     *
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
     * @param TableInterface $table
     *
     * @return TableManager
     */
    public function setMappingTable(TableInterface $table): TableManager
    {
        $this->collection->set($table);
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
     *
     * @return TableManager
     */
    public function setStrictMode(bool $strictMode): TableManager
    {
        $this->collection->setStrictMode($strictMode);
        return $this;
    }
}
