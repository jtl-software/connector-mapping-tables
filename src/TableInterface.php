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
     * @param integer $type
     * @param integer $hostId
     * @return string|null
     */
    public function getEndpoint(int $type, int $hostId): ?string;

    /**
     * @param string $endpoint
     * @param integer $hostId
     * @return integer
     */
    public function save(string $endpoint, int $hostId): int;

    /**
     * @param integer $type
     * @param string $endpoint
     * @param integer $hostId
     * @return integer
     */
    public function remove(int $type, string $endpoint = null, int $hostId = null): int;

    /**
     * @param integer|null $type
     * @return integer
     */
    public function clear(int $type = null): int;

    /**
     * @param integer|null $type
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return integer
     */
    public function count(int $type = null, array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int;

    /**
     * @param integer|null $type
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return string[]
     */
    public function findEndpoints(int $type = null, array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array;

    /**
     * @param string[] $endpoints
     * @return string[]
     */
    public function filterMappedEndpoints(array $endpoints): array;
}
