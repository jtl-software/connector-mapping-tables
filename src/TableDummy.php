<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

class TableDummy implements TableInterface
{
    /**
     * @var integer[]
     */
    protected $types = [];

    /**
     * @return integer[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param integer $type
     */
    public function setType(int $type)
    {
        if (!in_array($type, $this->types, true)) {
            $this->types[] = $type;
        }
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
     * @param integer $type
     * @param integer $hostId
     * @return string
     */
    public function getEndpoint(int $type, int $hostId): ?string
    {
        return null;
    }

    /**
     * @param string $endpoint
     * @param integer $hostId
     * @return integer
     */
    public function save(string $endpoint, int $hostId): int
    {
        return 0;
    }

    /**
     * @param integer $type
     * @param string|null $endpoint
     * @param integer|null $hostId
     * @return integer
     */
    public function remove(int $type, string $endpoint = null, int $hostId = null): int
    {
        return 0;
    }

    /**
     * @param integer|null $type
     * @return integer
     */
    public function clear(int $type = null): int
    {
        return 0;
    }

    /**
     * @param integer|null $type
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return integer
     */
    public function count(int $type = null, array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int
    {
        return 0;
    }

    /**
     * @param integer|null $type
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return array
     */
    public function findEndpoints(int $type = null, array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        return [];
    }

    /**
     * @param array $endpoints
     * @return array|string[]
     */
    public function filterMappedEndpoints(array $endpoints): array
    {
        return $endpoints;
    }
}
