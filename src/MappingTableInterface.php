<?php
namespace Jtl\Connector\MappingTables;

/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
interface MappingTableInterface
{
    /**
     * @return integer
     */
    public function getType(): int;

    /**
     * Host ID getter
     *
     * @param string $endpoint
     * @return integer|null
     */
    public function getHostId(string $endpoint): ?int;

    /**
     * Endpoint ID getter
     *
     * @param integer $hostId
     * @return string|null
     */
    public function getEndpoint(int $hostId): ?string;

    /**
     * Save link to database
     *
     * @param string $endpoint
     * @param integer $hostId
     * @return boolean
     */
    public function save(string $endpoint, int $hostId): bool;

    /**
     * Delete link from database
     *
     * @param string $endpoint
     * @param integer $hostId
     * @return boolean
     */
    public function remove(string $endpoint = null, int $hostId = null): bool;

    /**
     * Clears the entire link table
     * @return integer
     */
    public function clear(): int;

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return integer
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int;

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return string[]
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array;

    /**
     * @param string[] $endpoints
     * @return string[]
     */
    public function findNotFetchedEndpoints(array $endpoints): array;
}
