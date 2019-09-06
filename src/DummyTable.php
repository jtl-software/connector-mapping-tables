<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

use Jtl\Connector\Dbc\Query\QueryBuilder;

class DummyTable implements MappingTableInterface
{
    /**
     * @var integer
     */
    protected $type;

    /**
     * DummyTable constructor.
     */
    public function __construct()
    {
        $this->type = 0;
    }

    /**
     * @return integer
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param integer $type
     * @return DummyTable
     */
    public function setType($type): DummyTable
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $endpoint
     * @return integer
     */
    public function getHostId(string $endpoint): ?int
    {
        return null;
    }

    /**
     * @param integer $hostId
     * @param string|null $relationType
     * @return string
     */
    public function getEndpoint(int $hostId, string $relationType = null): ?string
    {
        return null;
    }

    /**
     * @param string $endpoint
     * @param integer $hostId
     * @return booelan
     */
    public function save(string $endpoint, int $hostId): bool
    {
        return true;
    }

    /**
     * @param string|null $endpoint
     * @param int|null $hostId
     * @return boolean
     */
    public function remove(string $endpoint = null, int $hostId = null): bool
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return integer
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int
    {
        return 0;
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        return [];
    }

    /**
     * @param array $endpoints
     * @return array|string[]
     */
    public function findNotFetchedEndpoints(array $endpoints): array
    {
        return $endpoints;
    }

}
