<?php

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;

class TableProxy
{
    /**
     * @var integer
     */
    protected $type;

    /**
     * @var AbstractTable
     */
    protected $table;

    /**
     * TableProxy constructor.
     * @param int $type
     * @param AbstractTable $table
     * @throws MappingTablesException
     */
    public function __construct(int $type, AbstractTable $table)
    {
        $this->table = $table;
        $this->setType($type);
    }

    /**
     * @return integer
     * @throws Exception|MappingTablesException
     */
    public function clear(): int
    {
        return $this->table->clear($this->type);
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return int
     * @throws DBALException
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int
    {
        return $this->table->count($where, $parameters, $orderBy, $limit, $offset, $this->type);
    }

    /**
     * @param mixed ...$parts
     * @return string
     */
    public function createEndpoint(...$parts): string
    {
        if (!$this->table->isSingleIdentity()) {
            $parts[] = $this->type;
        }

        return $this->table->buildEndpoint($parts);
    }

    /**
     * @param string|null $endpoint
     * @param int|null $hostId
     * @return int
     * @throws DBALException|MappingTablesException
     */
    public function delete(string $endpoint = null, int $hostId = null): int
    {
        return $this->table->remove($endpoint, $hostId, $this->type);
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return array
     * @throws DBALException
     * @throws MappingTablesException
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        return $this->table->findEndpoints($where, $parameters, $orderBy, $limit, $offset, $this->type);
    }

    /**
     * @param array $endpoints
     * @return array
     * @throws DBALException
     */
    public function filterMappedEndpoints(array $endpoints): array
    {
        return $this->table->filterMappedEndpoints($endpoints);
    }

    /**
     * @param int $hostId
     * @return string|null
     * @throws Exception
     */
    public function getEndpoint(int $hostId): ?string
    {
        return $this->table->getEndpoint($hostId, $this->type);
    }

    /**
     * @param string $endpoint
     * @return int|null
     * @throws DBALException|MappingTablesException
     */
    public function getHostId(string $endpoint): ?int
    {
        return $this->table->getHostId($endpoint);
    }

    /**
     * @return TableInterface
     */
    public function getTable(): TableInterface
    {
        return $this->table;
    }

    /**
     * @return integer
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param string $endpoint
     * @param int $hostId
     * @return int
     * @throws DBALException|MappingTablesException
     */
    public function save(string $endpoint, int $hostId): int
    {
        return $this->table->save($endpoint, $hostId);
    }

    /**
     * @param integer $type
     * @return TableProxy
     * @throws MappingTablesException
     */
    public function setType(int $type): TableProxy
    {
        if (!in_array($type, $this->table->getTypes(), true)) {
            throw MappingTablesException::tableNotResponsibleForType($type);
        }

        $this->type = $type;
        return $this;
    }
}
