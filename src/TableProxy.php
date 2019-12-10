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
     * @var TableInterface
     */
    protected $table;

    /**
     * MappingTableDecorator constructor.
     * @param integer $type
     * @param TableInterface $table
     */
    public function __construct(int $type, TableInterface $table)
    {
        $this->setType($type);
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
     * @return integer
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
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

    /**
     * @param string $endpoint
     * @return integer|null
     * @throws DBALException
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
     * @return integer
     * @throws DBALException
     */
    public function save(string $endpoint, int $hostId): int
    {
        return $this->table->save($this->type, $endpoint, $hostId);
    }

    /**
     * @param string|null $endpoint
     * @param integer|null $hostId
     * @return integer
     * @throws DBALException
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
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return integer
     * @throws DBALException
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int
    {
        return $this->table->count($this->type, $where, $parameters, $orderBy, $limit, $offset);
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return array
     * @throws DBALException
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        return $this->table->findEndpoints($this->type, $where, $parameters, $orderBy, $limit, $offset);
    }

    /**
     * @param string[] $endpoints
     * @return string[]
     * @throws DBALException
     */
    public function filterMappedEndpoints(array $endpoints): array
    {
        return $this->table->filterMappedEndpoints($endpoints);
    }
}