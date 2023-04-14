<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

class TableDummy implements TableInterface
{
    /**
     * @var integer[]
     */
    protected array $types = [];

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
    public function setType(int $type): void
    {
        if (!\in_array($type, $this->types, true)) {
            $this->types[] = $type;
        }
    }

    /**
     * @param string $endpoint
     *
     * @return integer
     */
    public function getHostId(string $endpoint): ?int
    {
        return null;
    }

    /**
     * @param integer      $hostId
     * @param integer|null $type
     *
     * @return string
     */
    public function getEndpoint(int $hostId, int $type = null): ?string
    {
        return null;
    }

    /**
     * @param string  $endpoint
     * @param integer $hostId
     *
     * @return integer
     */
    public function save(string $endpoint, int $hostId): int
    {
        return 0;
    }

    /**
     * @param string|null  $endpoint
     * @param integer|null $hostId
     * @param integer|null $type
     *
     * @return integer
     */
    public function remove(string $endpoint = null, int $hostId = null, int $type = null): int
    {
        return 0;
    }

    /**
     * @param integer|null $type
     *
     * @return integer
     */
    public function clear(int $type = null): int
    {
        return 0;
    }

    /**
     * @param string[]     $where
     * @param string[]     $parameters
     * @param string[]     $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @param integer|null $type
     *
     * @return integer
     */
    public function count(
        array $where = [],
        array $parameters = [],
        array $orderBy = [],
        int   $limit = null,
        int   $offset = null,
        int   $type = null
    ): int {
        return 0;
    }

    /**
     * @param string[]     $where
     * @param string[]     $parameters
     * @param string[]     $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @param integer|null $type
     *
     * @return array<empty>
     */
    public function findEndpoints(
        array $where = [],
        array $parameters = [],
        array $orderBy = [],
        int   $limit = null,
        int   $offset = null,
        int   $type = null
    ): array {
        return [];
    }

    /**
     * @param array|string[] $endpoints
     *
     * @return array|string[]
     */
    public function filterMappedEndpoints(array $endpoints): array
    {
        return $endpoints;
    }
}
