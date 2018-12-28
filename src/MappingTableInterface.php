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
     * @param string $endpointId
     * @return integer|null
     */
    public function getHostId($endpointId);

    /**
     * Endpoint ID getter
     *
     * @param integer $hostId
     * @param string|null $relationType
     * @return string|null
     */
    public function getEndpointId($hostId, $relationType = null);

    /**
     * Save link to database
     *
     * @param string $endpointId
     * @param integer $hostId
     * @return integer
     */
    public function save($endpointId, $hostId);

    /**
     * Delete link from database
     *
     * @param string $endpointId
     * @param integer $hostId
     * @return integer
     */
    public function remove($endpointId = null, $hostId = null);

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
     * @return string[]
     */
    public function findAllEndpoints();

    /**
     * @param string[] $endpoints
     * @return string[]
     */
    public function findNotFetchedEndpoints(array $endpoints);
}