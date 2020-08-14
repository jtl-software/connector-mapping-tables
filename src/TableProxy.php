<?php

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\DBALException;

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
     */
    public function __construct(int $type, AbstractTable $table)
    {
        $this->table = $table;
        $this->setType($type);
    }

    /**
     * @return integer
     */
    public function clear(): int
    {
        return $this->table->clear($this->type);
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return integer
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int
    {
        return $this->table->count($this->type, $where, $parameters, $orderBy, $limit, $offset);
    }

    /**
     * @param mixed ...$parts
     * @return string
     */
    public function createEndpoint(...$parts): string
    {
        return $this->table->buildEndpoint(array_merge($parts, [$this->type]));
    }

    /**
     * @param string|null $endpoint
     * @param integer|null $hostId
     * @return integer
     */
    public function delete(string $endpoint = null, int $hostId = null): int
    {
        return $this->table->remove($this->type, $endpoint, $hostId);
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return array
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        return $this->table->findEndpoints($this->type, $where, $parameters, $orderBy, $limit, $offset);
    }

    /**
     * @param array $endpoints
     * @return array
     */
    public function filterMappedEndpoints(array $endpoints): array
    {
        return $this->table->filterMappedEndpoints($endpoints);
    }

    /**
     * @param integer $hostId
     * @return string|null
     */
    public function getEndpoint(int $hostId): ?string
    {
        return $this->table->getEndpoint($this->type, $hostId);
    }

    /**
     * @param string $endpoint
     * @return integer|null
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
     * @param integer $hostId
     * @return integer
     */
    public function save(string $endpoint, int $hostId): int
    {
        return $this->table->save($endpoint, $hostId);
    }

    /**
     * @param integer $type
     * @return TableProxy
     */
    public function setType(int $type): TableProxy
    {
        if (!in_array($type, $this->table->getTypes(), true)) {
            throw RuntimeException::typeNotFound($type);
        }

        $this->type = $type;
        return $this;
    }
}
