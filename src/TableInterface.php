<?php

namespace Jtl\Connector\MappingTables;

/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
interface TableInterface
{
    /**
     * @return integer[]
     */
    public function getTypes(): array;

    /**
     * @param string $endpoint
     * @return integer|null
     */
    public function getHostId(string $endpoint): ?int;

    /**
     * @param integer $hostId
     * @param integer|null $type
     * @return string|null
     */
    public function getEndpoint(int $hostId, int $type = null): ?string;

    /**
     * @param string $endpoint
     * @param integer $hostId
     * @return integer
     */
    public function save(string $endpoint, int $hostId): int;

    /**
     * @param string|null $endpoint
     * @param int|null $hostId
     * @param integer|null $type
     * @return integer
     */
    public function remove(string $endpoint = null, int $hostId = null, int $type = null): int;

    /**
     * @param integer|null $type
     * @return integer
     */
    public function clear(int $type = null): int;

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @param integer|null $type
     * @return integer
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null, int $type = null): int;

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @param integer|null $type
     * @return string[]
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null, int $type = null): array;

    /**
     * @param string[] $endpoints
     * @return string[]
     */
    public function filterMappedEndpoints(array $endpoints): array;
}
