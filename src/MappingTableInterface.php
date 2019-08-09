<?php
namespace jtl\Connector\MappingTables;
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
interface MappingTableInterface
{
    /**
     * @return mixed
     */
    public function getType();

    /**
     * Host ID getter
     *
     * @param string $endpoint
     * @return integer|null
     */
    public function getHostId($endpoint);

    /**
     * Endpoint ID getter
     *
     * @param integer $hostId
     * @param string|null $relationType
     * @return string|null
     */
    public function getEndpoint($hostId, $relationType = null);

    /**
     * Save link to database
     *
     * @param string $endpoint
     * @param integer $hostId
     * @return integer
     */
    public function save($endpoint, $hostId);

    /**
     * Delete link from database
     *
     * @param string $endpoint
     * @param integer $hostId
     * @return integer
     */
    public function remove($endpoint = null, $hostId = null);

    /**
     * Clears the entire link table
     *
     * @return integer
     */
    public function clear();

    /**
     * @return integer
     */
    public function count();

    /**
     * @param array $where
     * @param array $parameters
     * @return mixed
     */
    public function findEndpoints(array $where = [], array $parameters = []);

    /**
     * @param string[] $endpoints
     * @return string[]
     */
    public function findNotFetchedEndpoints(array $endpoints);
}
