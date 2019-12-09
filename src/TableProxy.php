<?php
namespace Jtl\Connector\MappingTables;

class TableProxy
{
    /**
     * @var integer
     */
    protected $type;

    /**
     * @var TableInterface
     */
    protected $table;

    /**
     * MappingTableDecorator constructor.
     * @param int $type
     * @param TableInterface $table
     */
    public function __construct(int $type, TableInterface $table)
    {
        if(!in_array($type, $table->getTypes(), true)) {
            throw RuntimeException::typeNotFound($type);
        }

        $this->type = $type;
        $this->table = $table;
    }

    /**
     * @return TableInterface
     */
    public function getTable(): TableInterface
    {
        return $this->table;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param string $endpoint
     * @return integer|null
     */
    public function getHostId(string $endpoint): ?int
    {
        return $this->table->getHostId($this->type, $endpoint);
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
     * @param integer $hostId
     * @return boolean
     */
    public function save(string $endpoint, int $hostId): int
    {
        return $this->table->save($this->type, $endpoint, $hostId);
    }

    /**
     * @param string $endpoint
     * @param integer $hostId
     * @return int
     */
    public function delete(string $endpoint = null, int $hostId = null): int
    {
        return $this->table->delete($this->type, $endpoint, $hostId);
    }

    /**
     * @return integer
     */
    public function clear(): int
    {
        return $this->table->clear($this->type);
    }

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return integer
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int
    {
        return $this->table->count($this->type, $where, $parameters, $orderBy, $limit, $offset);
    }

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return string[]
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        return $this->table->findEndpoints($this->type, $where, $parameters, $orderBy, $limit, $offset);
    }

    /**
     * @param string[] $endpoints
     * @return string[]
     */
    public function filterMappedEndpoints(array $endpoints): array
    {
        return $this->table->filterMappedEndpoints($endpoints);
    }
}